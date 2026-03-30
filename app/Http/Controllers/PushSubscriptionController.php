<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyPushSubscriptionRequest;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\PppUser;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'subscribable_type' => User::class,
                'subscribable_id' => $user->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ],
        );

        return response()->json(['success' => true]);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        PushSubscription::query()
            ->where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->where('endpoint', $request->validated('endpoint'))
            ->delete();

        return response()->json(['success' => true]);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('push.vapid.public_key', ''),
        ]);
    }

    public function portalStore(StorePushSubscriptionRequest $request): JsonResponse
    {
        /** @var PppUser|null $pppUser */
        $pppUser = $request->attributes->get('portal_ppp_user');
        abort_unless($pppUser instanceof PppUser, 401);

        $data = $request->validated();

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'subscribable_type' => PppUser::class,
                'subscribable_id' => $pppUser->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ],
        );

        return response()->json(['success' => true]);
    }

    public function portalDestroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        /** @var PppUser|null $pppUser */
        $pppUser = $request->attributes->get('portal_ppp_user');
        abort_unless($pppUser instanceof PppUser, 401);

        PushSubscription::query()
            ->where('subscribable_type', PppUser::class)
            ->where('subscribable_id', $pppUser->id)
            ->where('endpoint', $request->validated('endpoint'))
            ->delete();

        return response()->json(['success' => true]);
    }
}
