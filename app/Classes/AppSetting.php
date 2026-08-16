<?php

namespace App\Classes;

class AppSetting
{
   private $settingsFile;
   private $settings;

   function __construct() // initialize settingsFile ( string ) && initialize settings array variables
   {
       $this->settingsFile = $this->getFile();
       $this->settings     = is_array($this->settingsFile) ? $this->settingsFile : [];
   }

   public function get( $key = null ) // get setting with specific key or get all settings if the key is null
   {

       if ( $key )
           return $this->settings[$key] ?? null;
       else
           return $this->settings;

   }

    public function set($key,$value) // set a new key with the given value
    {
        // Equivalent of the Setting model's saving guard for the JSON store.
        // Common::stopSwitch reads DB OR JSON, so a protected money key written
        // JSON-only would never trip the model guard. Normalize identically to
        // the model (DB collation is case-insensitive) and block outside the
        // sanctioned withMoneyKeyWrite() gate.
        $normalized = mb_strtolower(trim((string) $key));
        if (
            in_array($normalized, \App\Helpers\Common::PROTECTED_MONEY_KEYS, true)
            && !\App\Helpers\Common::$moneyKeyWriteAllowed
        ) {
            throw new \RuntimeException(
                'Protected money key write blocked outside sanctioned path: ' . $key
            );
        }

        $this->settings = $this->mutateUnderLock(function (array $current) use ($key, $value) {
            $current[$key] = $value;
            return $current;
        });

        return $this->settings;
    }

    public function remove($key) // remove the entry with the given key ( return null if the key not exist else return the entire array)
    {
        $removed = false;

        $this->settings = $this->mutateUnderLock(function (array $current) use ($key, &$removed) {
            if (array_key_exists($key, $current)) {
                unset($current[$key]);
                $removed = true;
            }
            return $current;
        });

        return $removed ? $this->settings : null;
    }

    /**
     * Canonical storage location. Lives under storage/ (NOT public/) because in
     * production each Swarm replica bind-mounts storage/ as a DIRECTORY, so an
     * atomic rename inside it works and every replica sees the same inode.
     * public/settings.json was bind-mounted as a single FILE, which made it a
     * mountpoint inside the container — rename(2) over a mountpoint fails with
     * EBUSY, so every write was silently lost.
     */
    private function dataPath() : string
    {
        return storage_path('app/settings.json');
    }

    private function getFile() : array // get the file content decoded as an array (safe: never throws)
    {
        $filePath = $this->dataPath();

        if (!is_file($filePath)) {
            // Legacy seed: fall back to the old public/ location so existing
            // values survive the move. First successful write persists them
            // to the new path.
            $filePath = public_path('settings.json');
        }

        if (!is_file($filePath)) {
            return [];
        }

        $content = @file_get_contents($filePath);
        if ($content === false || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Concurrency-safe read-modify-write.
     *
     * Acquires an exclusive lock on a DEDICATED lock file (settings.json.lock)
     * that is never renamed, then — while holding that lock — RE-READS the fresh
     * on-disk content of settings.json directly from its path, applies $mutator
     * to that fresh array, and writes the result atomically via a temp file in
     * the SAME directory + rename (so a SIGKILL mid-write can never truncate or
     * corrupt the live file). Returns the persisted array.
     *
     * WHY A SEPARATE LOCK FILE (not flock on the data file):
     *   flock() is bound to an inode, not to a pathname. atomicWrite() replaces
     *   settings.json with a brand-new inode via rename(). If the lock were held
     *   on the data file's handle, after the first writer's rename that lock
     *   would protect a stale (now-unlinked) inode, while a second writer waiting
     *   on the data file could acquire a lock on the NEW inode, re-read a stale
     *   snapshot and clobber the first writer's update — a lost update that is
     *   still possible on POSIX. Locking a file whose inode is never replaced
     *   (settings.json.lock) truly serializes writers, so every re-read sees the
     *   latest committed version. It also avoids the rename-over-open-handle
     *   failure mode on platforms that forbid it.
     *
     * Safety: if anything about the lock/temp-write path fails, the live file is
     * left untouched and the in-memory array is returned unchanged. A corrupt
     * (non-array) on-disk JSON is treated as [] for mutation purposes, but the
     * mutator only ever ADDS/REMOVES the targeted key on top of whatever else
     * was readable, so it does not blindly clobber unrelated data.
     */
    private function mutateUnderLock(callable $mutator) : array
    {
        $filePath = $this->dataPath();
        $lockPath = $this->dataPath() . '.lock';

        // 'c' : open for write, create if missing, do NOT truncate. This handle
        // is used ONLY for flock — its inode is stable and never renamed, so the
        // lock genuinely serializes concurrent writers.
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            // Could not open the lock file at all: apply the mutation in memory
            // only so the caller's return value stays consistent, persist nothing.
            return $mutator(is_array($this->settings) ? $this->settings : []);
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                return $mutator(is_array($this->settings) ? $this->settings : []);
            }

            // Re-read the CURRENT on-disk content of the DATA file under the lock,
            // straight from its path (never a stale handle). This is what
            // prevents lost updates from a stale constructor read.
            $current = $this->getFile();

            $next = $mutator($current);
            if (!is_array($next)) {
                $next = $current;
            }

            $this->atomicWrite($filePath, $next);

            flock($lock, LOCK_UN);
            return $next;
        } finally {
            fclose($lock);
        }
    }

    /**
     * Atomic write: encode to a temp file in the SAME directory, then rename
     * over the target. rename(2) is atomic on POSIX when src and dst are on the
     * same filesystem, so readers see either the old or the new file in full,
     * never a half-written one.
     */
    private function atomicWrite(string $filePath, array $data) : void
    {
        $dir     = dirname($filePath);
        $encoded = json_encode($data);

        if ($encoded === false) {
            // Refuse to persist unencodable data rather than corrupt the file.
            return;
        }

        $tmp = tempnam($dir, 'set');
        if ($tmp === false) {
            return;
        }

        if (@file_put_contents($tmp, $encoded) === false) {
            @unlink($tmp);
            return;
        }

        if (!@rename($tmp, $filePath)) {
            @unlink($tmp);
        }
    }

    private function saveFile() // retained for backward compatibility; writes current in-memory state atomically
    {
        $this->atomicWrite($this->dataPath(), is_array($this->settings) ? $this->settings : []);
    }
}
