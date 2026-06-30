<?php

namespace App\Http\Controllers\Api\Concerns;

/**
 * EscapesLikeQuery Trait
 *
 * LIKE 検索時に % や _ などの SQL 特殊文字をエスケープするユーティリティ。
 * ユーザー入力をそのまま LIKE クエリに渡すと意図しない全件マッチや
 * インジェクションが発生するため、必ずこのメソッドを通してから渡す。
 *
 * 使い方:
 *   use Concerns\EscapesLikeQuery;
 *   ->where('name', 'like', '%' . $this->escapeLike($request->search) . '%')
 */
trait EscapesLikeQuery
{
    /**
     * LIKE 検索用に特殊文字をエスケープする
     *
     * エスケープ対象: % （任意の文字列）、_ （任意の1文字）、\ （エスケープ文字）
     *
     * @param  string $value  ユーザーが入力した検索文字列
     * @return string         エスケープ済みの文字列
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
