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
            ? Response::denyWithStatus(403, 'Tài khoản của bạn đã bị khóa và không thể đăng bình luận.')
            : Response::allow();
    }

    /**
     * User chỉ được sửa bình luận của chính mình trong vòng 15 phút sau khi đăng.
     * Admin được sửa bất kỳ bình luận nào bất cứ lúc nào.
     *
     * Được gọi qua: $this->authorize('update', $comment)
     */
    public function update(User $user, Comment $comment): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->id !== $comment->user_id) {
            return Response::denyWithStatus(403, 'Bạn không có quyền sửa bình luận này.');
        }

        // Chỉ cho phép sửa trong vòng 15 phút
        $editWindowMinutes = 15;
        if ($comment->created_at->diffInMinutes(now()) > $editWindowMinutes) {
            return Response::denyWithStatus(
                403,
                "Bình luận chỉ có thể chỉnh sửa trong vòng {$editWindowMinutes} phút sau khi đăng."
            );
        }

        return Response::allow();
    }

    /**
     * User chỉ được xóa bình luận của chính mình.
     * Admin được xóa bất kỳ bình luận nào.
     *
     * Được gọi qua: $this->authorize('delete', $comment)
     */
    public function delete(User $user, Comment $comment): Response
    {
        return ($user->isAdmin() || $user->id === $comment->user_id)
            ? Response::allow()
            : Response::denyWithStatus(403, 'Bạn không có quyền xóa bình luận này.');
    }

    /**
     * Chỉ Admin mới được khôi phục bình luận đã xóa mềm.
     *
     * Được gọi qua: $this->authorize('restore', $comment)
     */
    public function restore(User $user, Comment $comment): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::denyWithStatus(403, 'Chỉ Quản trị viên mới có thể khôi phục bình luận.');
    }
}
