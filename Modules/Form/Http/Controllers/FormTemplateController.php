<?php

namespace  Modules\Form\Http\Controllers;

use App\Helpers\Common;
use App\Models\Bd;

use App\Models\Agency;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Form\Entities\FormField;
use Modules\Form\Entities\FormRequest;
use Modules\Form\Entities\FormSection;
use Modules\Form\Entities\FormTemplate;
use Encore\Admin\Auth\Permission;
use Carbon\Carbon;


class FormTemplateController extends Controller
{
    public $permission_name = 'templates-form';
    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $templates = FormTemplate::with('sections.fields')->latest()->get();
        return $content
            ->title(__(''))
            ->description(__(''))
            ->row(function ($row) use ($templates) {
                $row->column(12, view('Form::form-templates.index', compact('templates')));
            });
    }

    public function show($id, Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('show-' . $this->permission_name);
        }
        $template = FormTemplate::with(['sections.fields'])->findOrFail($id);
        return $content
            ->title(__('Preview'))
            ->body(view('Form::form-templates.show', compact('template')));
    }

    public function create()
    {

        $template = new FormTemplate();
        return view('Form::form-templates.create', compact('template'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string',
            'form_type' => 'required|string',
            'description' => 'nullable|array',
            'sections' => 'required|array',
        ]);

        // Create form template
        $template = FormTemplate::create([
            'title' => $request->title,
            'form_type' => $request->form_type,
            'description' => $request->description,
            'created_by' => auth()->id() ?? 1,
            'is_active' => true,
        ]);

        // Create sections and fields
        if ($request->has('sections')) {
            foreach ($request->sections as $sectionData) {
                $section = FormSection::create([
                    'form_template_id' => $template->id,
                    'title' => $sectionData['title'],
                    'section_order' => $sectionData['order'],
                    'is_visible' => true,
                ]);

                if (isset($sectionData['fields'])) {
                    foreach ($sectionData['fields'] as $fieldData) {
                        // Handle options based on type
                        $options = null;
                        $dataSource = null;

                        if (isset($fieldData['options_type'])) {
                            if ($fieldData['options_type'] === 'custom' && isset($fieldData['custom_options'])) {
                                // Parse custom options (either JSON or line-separated)
                                $customOptions = $fieldData['custom_options'];
                                if (is_string($customOptions)) {
                                    // Try to parse as JSON first
                                    $decoded = json_decode($customOptions, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $options = $decoded;
                                    } else {
                                        // Parse as line-separated values
                                        $lines = array_filter(array_map('trim', explode("\n", $customOptions)));
                                        $options = array_combine($lines, $lines);
                                    }
                                }
                            } elseif ($fieldData['options_type'] === 'predefined' && isset($fieldData['data_source'])) {
                                $dataSource = $fieldData['data_source'];
                            }
                        }

                        FormField::create([
                            'section_id' => $section->id,
                            'field_label' => $fieldData['label'],
                            'field_name' => $fieldData['name'],
                            'field_type' => $fieldData['type'],
                            'widget_id' => $fieldData['widget_id'] ?? null,
                            'widget_config' => isset($fieldData['widget_config']) ? json_decode($fieldData['widget_config'], true) : null,
                            'placeholder' => $fieldData['placeholder'] ?? null,
                            'options' => $options,
                            'data_source' => $dataSource,
                            'is_required' => isset($fieldData['required']) && $fieldData['required'] == '1',
                            'is_enabled' => isset($fieldData['enabled']) && $fieldData['enabled'] == '1',
                            'field_order' => $fieldData['order'],
                        ]);
                    }
                }
            }
        }
        admin_success(__('Form template created successfully!'));
        return redirect(admin_url('form-templates'));
    }

    public function edit($id, Content $content)
    {

        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }
        try {
            $template = FormTemplate::with(['sections.fields.widget'])->findOrFail($id);

            return $content
                ->title(__('Edit Form Template'))
                ->body(view('Form::form-templates.edit', compact('template')));
        } catch (\ModelNotFoundException $e) {
            admin_error(__('Error'), __('Form Template not found.'));
            return redirect()->back();
        } catch (\Exception $e) {
            admin_error(__('Unexpected Error'), __('Something went wrong while loading the form template.'));
            return redirect()->back();
        }
    }

    public function update(Request $request,  $id)
    {
        \Log::channel('single')->info('========== FORM TEMPLATE UPDATE START ==========');
        \Log::channel('single')->info('Updating Form Template ID: ' . $id);
        \Log::channel('single')->info('Request Method: ' . $request->method());
        \Log::channel('single')->info('Request URL: ' . $request->fullUrl());
        \Log::channel('single')->info('Request Headers:', $request->headers->all());
        \Log::channel('single')->info('Request Data:', $request->all());

        try {

            $formTemplate = FormTemplate::findOrFail($id);

            \Log::channel('single')->info('Current Template Data BEFORE update:', [
                'id' => $formTemplate->id,
                'title' => $formTemplate->getRawOriginal('title'),
                'form_type' => $formTemplate->form_type,
                'description' => $formTemplate->getRawOriginal('description'),
            ]);

            try {
                $validated = $request->validate([
                    'title' => 'required|array',
                    'title.*' => 'required|string',
                    'form_type' => 'required|string',
                    'description' => 'nullable|array',
                    'sections' => 'required|array',
                ]);
                \Log::channel('single')->info('Validation PASSED', $validated);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::channel('single')->error('Validation FAILED', ['errors' => $e->errors()]);
                throw $e;
            }

            $allFieldNames = [];
            foreach ($request->sections as $sectionData) {

                if (isset($sectionData['fields'])) {
                    foreach ($sectionData['fields'] as $fieldData) {
                        $name = trim($fieldData['name'] ?? '');
                        if ($name !== '') {
                            if (in_array($name, $allFieldNames)) {
                                \Log::channel('single')->warning('Duplicate field name detected: ' . $name);
                                return back()->withErrors(['duplicate_field' => "حقل '$name' مكرر داخل نفس النموذج."])->withInput();
                            }
                            $allFieldNames[] = $name;
                        }
                    }
                }
            }

            // Update form template basic info
            \Log::channel('single')->info('Attempting to update template basic info:', [
                'title' => $request->title,
                'form_type' => $request->form_type,
                'description' => $request->description,
            ]);

            $updateResult = $formTemplate->update([
                'title' => $request->title,
                'form_type' => $request->form_type,
                'description' => $request->description,
            ]);

            \Log::channel('single')->info('Template update() result: ' . ($updateResult ? 'TRUE' : 'FALSE'));

            // Reload and verify
            $formTemplate->refresh();
            \Log::channel('single')->info('Template Data AFTER update:', [
                'title' => $formTemplate->getRawOriginal('title'),
                'form_type' => $formTemplate->form_type,
                'description' => $formTemplate->getRawOriginal('description'),
                'dirty' => $formTemplate->getDirty(),
                'wasChanged' => $formTemplate->wasChanged(),
            ]);

            // Delete old sections and fields (cascade will handle fields)
            \Log::channel('single')->info('Deleting old sections for template ID: ' . $id);
            $oldSectionsCount = $formTemplate->sections()->count();
            $formTemplate->sections()->delete();
            \Log::channel('single')->info('Deleted ' . $oldSectionsCount . ' old sections');

            // Create new sections and fields
            if ($request->has('sections')) {
                foreach ($request->sections as $sectionData) {
                    // Create section
                    $section = FormSection::create([
                        'form_template_id' => $formTemplate->id,
                        'title' => $sectionData['title'],
                        'section_order' => $sectionData['order'],
                        'is_visible' => true,
                        'can_not_delete' => $sectionData['can_not_delete']
                    ]);

                    // Create fields for this section
                    if (isset($sectionData['fields'])) {
                        foreach ($sectionData['fields'] as $fieldData) {
                            $fieldType = $fieldData['type'] ?? 'text';
                            $options = null;
                            $dataSource = null;
                            $widgetId = null;
                            $widgetConfig = null;

                            // ============================================
                            // Handle Custom Widget Fields
                            // ============================================
                            if ($fieldType === 'custom' && isset($fieldData['widget_id'])) {
                                $widgetId = $fieldData['widget_id'];
                                $widget = \Modules\Form\Entities\CustomFieldWidget::find($widgetId);
                                if ($widget) {
                                    $widgetConfig = $widget->default_config;
                                }
                            }
                            // ============================================
                            // Handle Select/Checkbox/Radio Fields
                            // ============================================
                            elseif (in_array($fieldType, ['select', 'checkbox', 'radio'])) {
                                // Check if options_type is provided
                                if (isset($fieldData['options_type'])) {

                                    // Custom Options
                                    if ($fieldData['options_type'] === 'custom') {
                                        // Process custom options from the form
                                        // Format: sections[X][fields][Y][options][Z][label][locale] & [value]
                                        if (isset($fieldData['options']) && is_array($fieldData['options'])) {
                                            $processedOptions = [];

                                            foreach ($fieldData['options'] as $optionData) {
                                                if (isset($optionData['value']) && !empty($optionData['value'])) {
                                                    $processedOptions[] = [
                                                        'label' => $optionData['label'] ?? [],  // Multi-language labels
                                                        'value' => $optionData['value']
                                                    ];
                                                }
                                            }

                                            $options = !empty($processedOptions) ? $processedOptions : null;
                                        }
                                    }
                                    // Predefined Data Source
                                    elseif ($fieldData['options_type'] === 'predefined') {
                                        if (isset($fieldData['data_source']) && !empty($fieldData['data_source'])) {
                                            $dataSource = $fieldData['data_source'];
                                        }
                                    }
                                } elseif ($fieldData['options_type'] === 'predefined' && isset($fieldData['data_source'])) {
                                    $dataSource = $fieldData['data_source'];
                                }
                            }
                            // ============================================
                            // Create Field Record
                            // ============================================
                            FormField::create([
                                'section_id' => $section->id,
                                'field_label' => $fieldData['label'],
                                'field_name' => trim($fieldData['name']),
                                'field_type' => $fieldType,
                                'widget_id' => $widgetId,
                                'widget_config' => $widgetConfig,
                                'placeholder' => $fieldData['placeholder'] ?? null,
                                'options' => $options,
                                'data_source' => $dataSource,
                                'is_required' => isset($fieldData['required']) && $fieldData['required'] == '1',
                                'is_enabled' => isset($fieldData['enabled']) && $fieldData['enabled'] == '1',
                                'field_order' => $fieldData['order'],
                                'can_not_delete' =>  $fieldData['can_not_delete']
                            ]);
                        }
                    }
                }
            }

            \Log::channel('single')->info('========== FORM TEMPLATE UPDATE COMPLETED SUCCESSFULLY ==========');
            \Log::channel('single')->info('New sections count: ' . $formTemplate->sections()->count());
            \Log::channel('single')->info('Final template state:', [
                'id' => $formTemplate->id,
                'title' => $formTemplate->getRawOriginal('title'),
                'form_type' => $formTemplate->form_type,
            ]);

            admin_success(__('Form template updated successfully!'));
            return redirect(admin_url('form-templates'));
        } catch (\Exception $e) {
            \Log::channel('single')->error('========== FORM TEMPLATE UPDATE FAILED ==========');
            \Log::channel('single')->error('Error Message: ' . $e->getMessage());
            \Log::channel('single')->error('Error File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::channel('single')->error('Error Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }




    public function destroy(FormTemplate $formTemplate)
    {
        $formTemplate->delete();
        return redirect(admin_url('form-templates'))
            ->with('success', __('Form template deleted successfully!'));
    }

    public function showByType(Request $request)
    {

        $type = $request->query('type') ?? $request->type;
        $linkToken = $request->query('token') ?? $request->token;
        $defaultLang = $request->query('lang') ?? app()->getLocale();

        if (empty($linkToken)) {
            return response()->view('Form::forms.invalid', [
                'message' => 'Access denied. Please provide a valid token.',
            ], 403);
        }

        $token = PersonalAccessToken::findToken($linkToken);
        if (!$token) {
            return response()->view('Form::forms.invalid', [
                'message' => 'Invalid or expired token.',
            ], 403);
        }

        $user = $token->tokenable;

        if (!$user) {
            return response()->view('Form::forms.invalid', [
                'message' => 'User not found for this token.',
            ], 403);
        }

        $locale = $request->header('Accept-Language', $defaultLang);
        $locale = in_array($locale, ['ar', 'en', 'tr', 'hi']) ? $locale : $defaultLang;
        app()->setLocale($locale);

        $template = FormTemplate::with(['sections.fields'])
            ->where('form_type', $type)
            ->where('is_active', true)
            ->firstOrFail();

        return view('Form::web-view.dynamic-form', compact('template', 'locale', 'linkToken', 'user'));
    }



    public function storeSubmission(Request $request, string $type)
    {

        $template = FormTemplate::where('form_type', $type)->firstOrFail();

        $data = $request->except('_token');

        $existingRequest = FormRequest::where('form_template_id', $template->id)
            ->where('submitted_by', $request->user_id)->where('status', '=', 'pending')
            ->first();
        if ($existingRequest) {
            return redirect()->route('forms.show.reqs', [
                'id' => $existingRequest->id,
                'token' => $request->token,
                'lang' => $request->lang,
                'user_id' => $request->user_id,
            ]);
        }

        $data = $this->processFiles($data);

        $fields = $request->fields ?? [];



        $save = FormRequest::create([
            'form_template_id' => $template->id,
            'submitted_by' => $request->user_id,
            'bd_id' => $request->bd_id,
            'name' => $request->agency_name ?? $request->bd_name,
            'whatsapp_number' => $request->whatsapp_number,
            'form_template_type' => $template?->form_type,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'country' => $request->country_id,
            'created_at' => now(),
        ]);

        if (!$save) {
            return response()->json([
                'success' => false,
                'message' => __('something got wrong'),

            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('Request under review!'),
            'data' => [
                'order_id' => $save->id,
            ]
        ], 200);
    }


    protected function processFiles($input)
    {
        foreach ($input as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $input[$key] = Common::upload('data', $value);
            } elseif (is_array($value)) {
                $input[$key] = $this->processFiles($value);
            }
        }
        return $input;
    }


    public function getTranslations(Request $request)
    {
        $locale = $request->get('locale', 'en');
        $templateId = $request->get('id', 'en');

        $template = FormTemplate::with('sections.fields')->findOrFail($templateId);

        return response()->json([
            'title' => $template->getTranslation('title', $locale),
            'sections' => $template->sections->map(function ($section) use ($locale) {
                return [
                    'title' => $section->getTranslation('title', $locale),
                    'fields' => $section->fields->map(function ($field) use ($locale) {
                        $options = null;
                        if ($field->options) {
                            $optionsArray = is_array($field->options) ? $field->options : json_decode($field->options, true);
                            $options = collect($optionsArray)->map(function ($value) use ($locale) {
                                if (is_array($value)) {
                                    return $value[$locale] ?? $value['en'] ?? $value;
                                }
                                return $value;
                            })->toArray();
                        }

                        return [
                            'label' => $field->getTranslation('field_label', $locale),
                            'placeholder' => $field->getTranslation('placeholder', $locale),
                            'options' => $options,
                        ];
                    })
                ];
            })
        ]);
    }


    public function search(Request $request)
    {
        $query = $request->get('query');
        $lang = $request->get('lang') ?? app()->getLocale();

        app()->setLocale($lang);
        $bds = Bd::where('name', 'like', "%{$query}%")
            ->orWhere('id', 'like', "%{$query}%")
            ->limit(6)
            ->get();

        $results = $bds->map(function ($bd) {

            $topAgencies = Agency::where('bd_id', $bd->id)
                ->withCount('members')
                ->orderByDesc('members_count')
                ->limit(3)
                ->get(['img', 'name', 'members_count'])
                ->map(function ($agency) {
                    $defaultAgencyImage = asset("images/agency-placeholder.jpg");
                    $agencyImage = getImagePath($agency->img) ?? $defaultAgencyImage;

                    if (!isImageExists($agencyImage)) {
                        $agencyImage = $defaultAgencyImage;
                    }

                    return [
                        'name' => $agency->name,
                        'members_count' => $agency->members_count,
                        'image' => $agencyImage,
                    ];
                });


            $createdAt = Carbon::parse($bd->created_at);
            $now = now();
            $diffInYears = $createdAt->diffInYears($now);
            $diffInMonths = $createdAt->diffInMonths($now);
            $diffInDays = $createdAt->diffInDays($now);

            if ($diffInYears >= 1) {
                $since = __('Works since :value years', ['value' => $diffInYears]);
            } elseif ($diffInMonths >= 1) {
                $since = __('Works since :value months', ['value' => $diffInMonths]);
            } else {
                $since = __('Works since :value days', ['value' => $diffInDays]);
            }

            $defaultImage = asset("images/businessman-icon.jpg");
            $bdAvatar = getImagePath($bd->avatar) ?? $defaultImage;

            if (!isImageExists($bdAvatar)) {
                $bdAvatar = $defaultImage;
            }

            return [
                'id' => $bd->id,
                'name' => $bd->username,
                'phone' => $bd->phone_code . $bd->phone,
                'image' => $bdAvatar,
                'country' => $bd->country?->name,
                'is_default' => $bd->default,
                'bio' => __('form_bd_bio'),
                'years' => $since,
                'top_agencies' => $topAgencies,
            ];
        });

        return response()->json(['data' => $results]);
    }


    public function checkName(Request $request)
    {
        $name = trim($request->get('name'));

        if (!$name) {
            return response()->json([
                'status' => false,
                'message' => 'Name is required',
            ]);
        }

        $exists = FormField::where('field_name', $name)->exists();

        return response()->json([
            'status' => true,
            'exists' => $exists,
            'message' => $exists ? 'Name already exists' : 'Name is available',
        ]);
    }

    public function showReqs($id)
    {
        $formRequest = FormRequest::with('template')->findOrFail($id);

        $token = request('token');
        $lang = request('lang');
        $user_id = request('user_id');
        if ($lang) {
            app()->setLocale($lang);
        }
        return view('Form::forms.show_request', compact('formRequest', 'token', 'lang', 'user_id'));
    }

    public function destroyReqs($id, Request $request)
    {


        $linkToken = $request->token;
        $defaultLang = $request->lang;
        if (empty($linkToken)) {
            return response()->view('Form::forms.invalid', [
                'message' => 'Access denied. Please provide a valid token.',
            ], 403);
        }

        $token = PersonalAccessToken::findToken($linkToken);
        if (!$token) {
            return response()->view('Form::forms.invalid', [
                'message' => 'Invalid or expired token.',
            ], 403);
        }

        $user = $token->tokenable;

        if (!$user) {
            return response()->view('Form::forms.invalid', [
                'message' => 'User not found for this token.',
            ], 403);
        }

        $formRequest = FormRequest::with('template')->findOrFail($id);
        $type = $formRequest->template->form_type;
        $formRequest->delete();
        return redirect()->to(route('forms.showByType') . "?type={$type}&token={$linkToken}&lang={$defaultLang}");
    }




    public function removeRepetition()
    {
        DB::transaction(function () {

            // get duplicated form_types
            $duplicates = FormTemplate::select('form_type')
                ->groupBy('form_type')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('form_type');

            foreach ($duplicates as $formType) {

                // get all templates for this form_type ordered by oldest
                $templates = FormTemplate::where('form_type', $formType)
                    ->orderBy('id')
                    ->get();

                // keep first one
                $keep = $templates->shift();

                // delete duplicates
                foreach ($templates as $template) {

                    // delete related fields & sections safely
                    foreach ($template->sections as $section) {
                        $section->fields()->delete();
                    }

                    $template->sections()->delete();
                    $template->delete();
                }
            }
        });
        return response()->json([
            'message'    => 'remove repetition',

        ]);
    }
}
