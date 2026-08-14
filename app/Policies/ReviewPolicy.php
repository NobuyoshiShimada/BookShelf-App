<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * ログイン中のユーザーが指定されたレビューの編集画面を表示できるか認可判定
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  Review  $review  操作対象のレビューモデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function edit(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ログイン中のユーザーが指定されたレビューの情報を更新できるか認可判定
     *
     * 💡 認可仕様:
     * 自分が投稿したレビューのみ更新できるように制限し、他人のレビューに対する不正更新（403）をブロックします。
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  Review  $review  操作対象のレビューモデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ログイン中のユーザーが指定されたレビューをデータベースから完全に削除できるか認可判定
     *
     * 💡 認可仕様:
     * 自分が投稿したレビューのみ削除できるように制限し、他人のレビューに対する不正削除（403）をブロックします。
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  Review  $review  操作対象のレビューモデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
