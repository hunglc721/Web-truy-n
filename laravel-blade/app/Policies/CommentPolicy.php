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
     * User chỉ được sửa bình luận của chính mình trong cửa sổ thời gian cho phép.
     * Admin được sửa bất kỳ bình luận nào bất cứ lúc nào.
     *
     * Cửa sổ chỉnh sửa đọc từ config('comments.edit_window_minutes').
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

        $editWindowMinutes = (int) config('comments.edit_window_minutes', 15);

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

    /**
     * Admin có thể xem danh sách bình luận cần kiểm duyệt (kể cả pending/spam).
     * User thường chỉ xem được bình luận đã approved.
     *
     * Dùng cho Admin moderation panel.
     * Được gọi qua: $this->authorize('view', $comment)
     */
    public function view(User $user, Comment $comment): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // User thường chỉ được xem bình luận đã được duyệt
        return $comment->status === Comment::STATUS_APPROVED
            ? Response::allow()
            : Response::denyWithStatus(403, 'Bình luận này chưa được duyệt.');
    }
}
