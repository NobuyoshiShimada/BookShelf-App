<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * 書籍モデルインスタンスを指定されたレスポンス配列構造へとトランスフォーム（成形変換）
     *
     * 実装仕様:
     * N+1問題を防止するため、User, Genres, Reviews などのリレーションデータは
     * コントローラー側で Eager Loading（load）されている場合のみ動的に内包（whenLoaded）します。
     * また、集計値（平均評価点など）の浮動小数点数への厳格なキャストを担保します。
     *
     * @param  Request  $request  現在処理中の中央HTTPリクエストオブジェクト
     * @return array<string, mixed> クライアントへ返却するAPIレスポンス用連想配列
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'review_count' => $this->reviews_count ?? 0,
            'review_avg_rating' => $this->reviews_avg_rating !== null
             ? round((float) $this->reviews_avg_rating, 1) : null,
        ];
    }
}
