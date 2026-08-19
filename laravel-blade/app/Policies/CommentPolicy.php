<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Chỉ user chưa bị ban mới được đăng bình luận.
     *
     * Được gọi qua: $this->authorize('create', Comment::class)
     * hoặc: Gate::allows('create', Comment::class)
     */
    public function create(User $user): Response
    {
        return $user->isBanned()
            ? Response::deny('Tài khoản của bạn đã bị khóa và không thể đăng bình luận.', 403)
            : Response::allow();
    }

    /**
     * User chỉ được xóa bình luận của chính mình;
     * Admin được xóa bất kỳ bình luận nào.
     */
    public function delete(User $user, Comment $comment): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền xóa bình luận này.', 403);
    }
}
