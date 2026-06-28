<?php
/**
 * FixedAsset（固定資産）モデル
 * 有形・無形固定資産の取得から廃棄までのライフサイクルを管理する。
 * 減価償却計算（定額法・定率法）をモデル内で実行する。
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $fillable = [
        'asset_number', 'name', 'category', 'acquisition_date', 'acquisition_amount',
        'useful_life', 'depreciation_method', 'residual_value', 'disposal_date',
        'disposal_amount', 'note',
    ];

    protected $casts = [
        'acquisition_date'   => 'date',
        'disposal_date'      => 'date',
        'acquisition_amount' => 'decimal:2',
        'residual_value'     => 'decimal:2',
        'disposal_amount'    => 'decimal:2',
    ];

    /** 年間減価償却額（定額法） */
    public function annualDepreciationStraightLine(): float
    {
        return ($this->acquisition_amount - $this->residual_value) / $this->useful_life;
    }

    /** 年間減価償却率（定率法: 1 ÷ 耐用年数 × 2.0） */
    public function decliningBalanceRate(): float
    {
        return min(1.0, (1 / $this->useful_life) * 2.0);
    }

    /** 指定年度の期首帳簿価額 */
    public function bookValueAtYear(int $year): float
    {
        $years = $year - $this->acquisition_date->year;
        $bv    = (float) $this->acquisition_amount;
        if ($this->depreciation_method === 'straight_line') {
            $annual = $this->annualDepreciationStraightLine();
            $bv     = max($this->residual_value, $bv - $annual * $years);
        } else {
            $rate = $this->decliningBalanceRate();
            for ($i = 0; $i < $years; $i++) {
                $bv = max($this->residual_value, $bv * (1 - $rate));
            }
        }
        return round($bv, 2);
    }

    /** 廃棄済みか */
    public function isDisposed(): bool { return !is_null($this->disposal_date); }
}
