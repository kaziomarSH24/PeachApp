<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blocked;
use App\Models\Matching;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $users = User::count();
        $totalMatch = Matching::where('status', 'matched')->count();
        $totalReport = Blocked::where('reason', '!=', null)->count();
        $currentYear = $request->year ?? Carbon::now()->year;

        $userStats = User::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $totalMatchStats = Matching::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->where('status', 'matched')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $overview = [];
        for ($i = 1; $i <= 12; $i++) {
            $overview[] = [
                'month' => date("F", mktime(0, 0, 0, $i, 10)),
                'user_count' => $userStats->firstWhere('month', $i)->count ?? 0,
                'match_count' => $totalMatchStats->firstWhere('month', $i)->count ?? 0
            ];
        }
        $data = [
            'totalUsers' => $users,
            'totalMatch' => $totalMatch,
            'totalReport' => $totalReport,
            'chart_data' => $overview,
        ];
        return response()->json([
            'success' => true,
            'message' => 'Data retrieve successfully',
            'data' => $data
        ]);
    }


    //
    public function users(Request $request)
    {
        $query = User::select('id', 'first_name', 'last_name', 'avatar', 'email', 'role', 'status', 'address', 'created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('-', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
    public function user($id)
    {
        $user = User::select('id', 'first_name', 'last_name', 'email', 'role', 'status', 'address', 'created_at')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function updateUserStatus(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->status = $request->status;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong! Please try again later."
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong! Please try again later."
            ], 500);
        }
    }
}
