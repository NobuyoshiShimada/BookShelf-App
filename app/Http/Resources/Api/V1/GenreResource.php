<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    /**
     * ジャンルモデルインスタンスを指定されたレスポンス配列構造へとトランスフォーム（成形変換）
     *
     * @param  Request  $request  現在処理中の中央HTTPリクエストオブジェクト
     * @return array<string, mixed> クライアントへ返却するAPIレスポンス用連想配列
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
