<?php

namespace LoggedCloud\AuthClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LoggedCloud\AuthClient\Concerns\ResolvesUserModel;

class WebhookController
{
    use ResolvesUserModel;

    /**
     * Receive a webhook pushed from auth.logged.cloud. The body is HMAC-signed
     * with the shared secret so a change made at the identity provider (e.g. a
     * role change) propagates here immediately rather than at the next login.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('auth-client.webhook_secret');

        abort_if(empty($secret), 403, 'Webhooks are not configured.');

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $signature = (string) $request->header('X-LoggedCloud-Signature');

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid signature.');

        if ($request->input('event') === 'role.changed') {
            $this->applyRoleChange($request);
        }

        return response()->json(['ok' => true]);
    }

    protected function applyRoleChange(Request $request): void
    {
        $roleColumn = config('auth-client.columns.role');

        if (! $roleColumn) {
            return;
        }

        $model = $this->userModel();
        $authIdColumn = config('auth-client.columns.auth_id');

        $user = $model::query()
            ->where($authIdColumn, $request->input('auth_id'))
            ->first();

        if ($user) {
            $user->{$roleColumn} = $request->input('role');
            $user->save();
        }
    }
}
