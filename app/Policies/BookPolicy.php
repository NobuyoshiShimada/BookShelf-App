<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * ログイン中のユーザーが指定された書籍の情報を更新できるか認可判定
     *
     * 💡 認可仕様:
     * 自分が登録した書籍情報のみ更新できるように制限し、他人の書籍に対する不正更新（403）をブロックします。
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  Book  $book  操作対象の書籍モデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * ログイン中のユーザーが指定された書籍を完全に削除できるか認可判定
     *
     * 💡 認可仕様:
     * 自分が登録した書籍のみ削除できるように制限し、他人の書籍に対する不正削除（403）をブロックします。
     *
     * @param  User  $user  認証済みのログインユーザーインスタンス
     * @param  Book  $book  操作対象の書籍モデルインスタンス
     * @return bool 認可を通過させる（本人の）場合は true、拒否する場合は false
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
