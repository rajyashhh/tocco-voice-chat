<?php

namespace  Modules\Form\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Modules\Form\Entities\FormSubmission;
use Modules\Form\Entities\FormSubmissionValue;
use Modules\Form\Entities\FormTemplate;

class FormSubmissionController extends Controller
{
    /**
     * Display the form to users
     */
    public function show($templateId)
    {
        $template = FormTemplate::with(['sections.fields.widget'])
            ->where('is_active', true)
            ->findOrFail($templateId);

        return view('Form::forms.display', compact('template'));
    }

    /**
     * Submit the form
     */
    public function submit(Request $request, $templateId)
    {
        $template = FormTemplate::with(['sections.fields.widget'])
            ->where('is_active', true)
            ->findOrFail($templateId);

        // Build validation rules dynamically
        $rules = [];
        foreach ($template->sections as $section) {
            foreach ($section->fields as $field) {
                if ($field->is_required && $field->is_enabled) {
                    $rules['fields.' . $field->id] = 'required';
                }
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // Create form submission
            $submission = FormSubmission::create([
                'form_template_id' => $template->id,
                'entity_id' => $request->entity_id ?? 0,
                'entity_type' => $template->form_type,
                'submitted_by' => Auth::check() ? Auth::id() : null,
                'submission_status' => 'submitted',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Save field values
            if ($request->has('fields')) {
                foreach ($request->fields as $fieldId => $value) {
                    $field = $template->sections->flatMap->fields->where('id', $fieldId)->first();
                    
                    if (!$field) continue;

                    $submissionValue = new FormSubmissionValue();
                    $submissionValue->submission_id = $submission->id;
                    $submissionValue->field_id = $fieldId;

                    // Handle file uploads
                    if ($field->field_type === 'file' && $request->hasFile('fields.' . $fieldId)) {
                        $file = $request->file('fields.' . $fieldId);
                        $path = $file->store('form-submissions/' . $template->id, 'public');
                        
                        $submissionValue->file_path = $path;
                        $submissionValue->file_name = $file->getClientOriginalName();
                        $submissionValue->file_size = $file->getSize();
                        $submissionValue->field_value = $path;
                    }
                    // Handle custom widgets with multiple values
                    elseif ($field->hasCustomWidget() && $field->widget->allows_multiple && is_array($value)) {
                        $submissionValue->setValue($value);
                    }
                    // Handle regular fields
                    else {
                        $submissionValue->field_value = is_array($value) ? json_encode($value) : $value;
                    }

                    $submissionValue->save();
                }
            }

            DB::commit();

            return redirect()->route('forms.success', $submission->id)
                ->with('success', __('Form submitted successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => __('An error occurred while submitting the form. Please try again.')]);
        }
    }

    /**
     * Success page after submission
     */
    public function success($submissionId)
    {
        $submission = FormSubmission::with('template')->findOrFail($submissionId);

        return view('forms.success', compact('submission'));
    }

    /**
     * List all submissions for a template (for owners/admins)
     */
    public function index(Request $request)
    {
        $query = FormSubmission::with(['template', 'submitter'])
            ->latest();

        // Filter by template
        if ($request->has('template_id')) {
            $query->where('form_template_id', $request->template_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('submission_status', $request->status);
        }

        $submissions = $query->paginate(20);
        $templates = FormTemplate::all();

        return view('Form::submissions.index', compact('submissions', 'templates'));
    }

    /**
     * View a single submission (for owners/admins)
     */
    public function view($submissionId)
    {
        $submission = FormSubmission::with([
            'template.sections.fields.widget',
            'values.field',
            'submitter'
        ])->findOrFail($submissionId);

        return view('Form::submissions.view', compact('submission'));
    }

    /**
     * Update submission status
     */
    public function updateStatus(Request $request, $submissionId)
    {
        $submission = FormSubmission::findOrFail($submissionId);

        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        $submission->update([
            'submission_status' => $validated['status'],
        ]);

        return back()->with('success', __('Submission status updated successfully!'));
    }

    /**
     * Delete a submission
     */
    public function destroy($submissionId)
    {
        $submission = FormSubmission::findOrFail($submissionId);
        
        // Delete associated files
        foreach ($submission->values as $value) {
            if ($value->file_path) {
                Storage::disk('public')->delete($value->file_path);
            }
        }

        $submission->delete();

        return redirect()->route('submissions.index')
            ->with('success', __('Submission deleted successfully!'));
    }


    public function fix()
    {

        DB::table('form_fields')
            ->whereIn('field_name', [
                'total_salaries',
                'expected_hosts'
            ])
            ->update([
                'can_not_delete' => 0,
                'updated_at'     => now(),
            ]);

     
        $templates = DB::table('form_templates')
            ->orderBy('id') 
            ->get();

        if ($templates->count() > 3) {
            $idsToDelete = $templates
                ->slice(3)
                ->pluck('id')
                ->toArray();

            DB::table('form_templates')
                ->whereIn('id', $idsToDelete)
                ->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Form fields updated and extra templates removed successfully'
        ]);
    }
}
