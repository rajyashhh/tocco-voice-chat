<?php

namespace App\Traits;

trait MultiQueryPagination
{
    /**
     * @param int $countPrimary
     * @param int $pagination
     * @param mixed $currentPage
     * @return float|int
     */
    public function getDiffCountWithPage(int $countPrimary, int $pagination, mixed $currentPage): int|float
    {
        return $countPrimary - ($pagination * $currentPage);
    }


    /**
     * @param $countPrimary
     * @param int $perPage
     * @param mixed $currentPage
     * @return array
     */
    public function getNewLimitAndOffset($countPrimary, int $perPage, mixed $currentPage): array
    {
        $primaryPageCount                   = (float) $countPrimary / $perPage;
        $numOfAdminsPages    = (int)$primaryPageCount;
        $diffWithCurrentPage = $currentPage - $numOfAdminsPages;

        $limit = $perPage;
        if ($diffWithCurrentPage == 1 ) {
            $limit = $perPage - ($countPrimary % $perPage);
            $limit = $limit == 0 ? $perPage : $limit;
        }

        if (($numOfAdminsPages == 0 && $diffWithCurrentPage == 1)) {
            $offset = 0;
        } else if ($countPrimary < $perPage && $diffWithCurrentPage == 2){

            $offset = $perPage - $countPrimary;
        } else if(($primaryPageCount - $numOfAdminsPages  )> 0.0){
            $offset = (($currentPage - 1) * $perPage) - (($countPrimary) % $perPage) + ($perPage * $numOfAdminsPages);
        } else {
            if ($countPrimary == 0) {
                $countPrimary = 1;
            }
            $offset = (($currentPage - 1) * $perPage) - (($countPrimary) % $perPage) + ($perPage * $numOfAdminsPages);

            //            $offset = $countPrimary % $perPage * (($diffWithCurrentPage - 1) * $perPage);
        }

        return [$limit, $offset];
    }
}