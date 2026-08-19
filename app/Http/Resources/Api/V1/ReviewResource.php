<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * レビューモデルインスタンスを指定されたレスポンス配列構造へとトランスフォーム（成形変換）
     *
     * 実装仕様:
     * N+1問題を強固に防止するため、投稿者ユーザー（user）や対象書籍（book）の情報は、
     * コントローラー側で事前にロードされている場合のみ動的に内包（whenLoaded）します。
     *
     * @param  Request  $request  現在処理中の中央HTTPリクエストオブジェクト
     * @return array<string, mixed> クライアントへ返却するAPIレスポンス用連想配列
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'book' => new BookResource($this->whenLoaded('book')),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'likes_count' => $this->liked_by_users_count ?? 0,
        ];
    }
}
