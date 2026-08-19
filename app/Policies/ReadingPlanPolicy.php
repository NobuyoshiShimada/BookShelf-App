<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * ログイン中のユーザーが指定された読書計画の詳細（編集画面など）を表示できるか認可判定
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  ReadingPlan  $readingPlan  操作対象の読書計画モデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * ログイン中のユーザーが指定された読書計画の目標期日を更新できるか認可判定
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  ReadingPlan  $readingPlan  操作対象の読書計画モデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * ログイン中のユーザーが指定された読書計画を完全に削除できるか認可判定
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  ReadingPlan  $readingPlan  操作対象の読書計画モデルインスタンス
     * @return bool 認過を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * ログイン中のユーザーが指定された書籍の読書計画を「読了（完了）」にできるか認可判定
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  ReadingPlan  $readingPlan  操作対象の読書計画モデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
