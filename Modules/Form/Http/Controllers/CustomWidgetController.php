<?php

namespace  Modules\Form\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Form\Entities\CustomFieldWidget;

class CustomWidgetController extends Controller
{

 
    /**
     * Get available widgets
     */
    public function index(): JsonResponse
    {
        $widgets = CustomFieldWidget::where('is_active', true)
            ->select('id', 'widget_type', 'widget_name', 'description', 'allows_multiple')
            ->get();

        return response()->json($widgets);
    }

    /**
     * Get widget data
     */
    public function getData(Request $request, $widgetId): JsonResponse
    {
        $widget = CustomFieldWidget::findOrFail($widgetId);
        
        // Get custom config from request or use default
        $config = $request->has('config') ? $request->input('config') : [];
        
        $data = $widget->getData($config);

        return response()->json([
            'widget' => $widget,
            'data' => $data,
        ]);
    }

    /**
     * Get BD users (Business Development managers)
     */
    public function getBDUsers(Request $request): JsonResponse
    {
        $query = User::where('is_active', true);

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        } elseif ($request->has('roles')) {
            $query->whereIn('role', $request->roles);
        } else {
            // Default to admin and owner for BD
            $query->whereIn('role', ['admin', 'owner']);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->select('id', 'name', 'email', 'role')->get();

        return response()->json($users);
    }

    /**
     * Get agencies (for agency selector widget)
     */
    public function getAgencies(Request $request): JsonResponse
    {
        $query = Agency::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'approved'); // Default to approved only
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('agency_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $agencies = $query->select('id', 'agency_name', 'agency_logo', 'description', 'status')
            ->with('creator:id,name,email')
            ->get();

        return response()->json($agencies);
    }

    /**
     * Get users for user picker widget
     */
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::where('is_active', true);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->select('id', 'name', 'email', 'role')->get();

        return response()->json($users);
    }
}
