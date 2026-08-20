<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Reading = 'reading';
    case Completed = 'completed';
    case Overdue = 'overdue';

    /**
     * 各ステータスに対応する画面表示用の日本語ラベルを返却
     *
     * @return string 日本語のステータス名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Reading => '読書中',
            self::Completed => '読了',
            self::Overdue => '期日超過',
        };
    }

    /**
     * 各ステータスを画面上に色分け（UI表現）するための Tailwind CSS バッジ用スタイルクラスを返却
     *
     * @return string Tailwind CSS の背景色・文字色を指定するクラス文字列
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Reading => 'bg-yellow-100 text-yellow-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Overdue => 'bg-red-100 text-red-800',
        };
    }
}
