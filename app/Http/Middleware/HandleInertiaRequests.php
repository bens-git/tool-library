<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\User;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Refresh the user to get latest data from database
        $user = $request->user();
        if ($user) {
            $user->refresh();
        }
        
        $notifications = [
            'unread_messages' => 0,
            'has_new_community_posts' => false,
        ];
        
        if ($user) {
            $notifications['unread_messages'] = $user->unreadMessageCount();
            $notifications['has_new_community_posts'] = $user->hasNewCommunityPosts();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'notifications' => $notifications,
            'csrfToken' => csrf_token(),
        ];
    }
}
