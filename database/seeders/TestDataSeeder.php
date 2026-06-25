<?php

namespace Database\Seeders;

use App\Models\AccountItem;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── ユーザー ──────────────────────────────────
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name'     => '管理者 太郎',
            'password' => Hash::make('password'),
        ]);
        $staff = User::firstOrCreate(['email' => 'staff@example.com'], [
            'name'     => '経理 花子',
            'password' => Hash::make('password'),
        ]);

        // ── 取引先 ────────────────────────────────────
        $clients = [
            ['C0001', '株式会社サンプルテック',    'サンプルテック',    'customer', '100-0001', '東京都千代田区丸の内1-1-1',   '03-1234-5678', 'contact@sampletech.co.jp',    '田中 一郎', 30],
            ['C0002', '有限会社フューチャーデザイン','フューチャーデザイン','customer', '150-0001', '東京都渋谷区神南1-2-3',       '03-2345-6789', 'info@futuredesign.jp',        '鈴木 次郎', 45],
            ['C0003', '合同会社グリーンソリューション','グリーンソリューション','customer','220-0001','神奈川県横浜市西区北幸2-3-4', '045-345-6789', 'hello@greensolution.co.jp',   '佐藤 三郎', 60],
            ['C0004', '株式会社ブルーウェーブ',    'ブルーウェーブ',    'vendor',   '530-0001', '大阪府大阪市北区梅田1-4-5',   '06-4567-8901', 'purchase@bluewave.jp',        '高橋 四郎', 30],
            ['C0005', '株式会社レッドコンサルティング','レッドコンサルティング','both','460-0001','愛知県名古屋市中区栄2-5-6',  '052-567-8901', 'sales@redconsulting.co.jp',   '伊藤 五郎', 30],
            ['C0006', '株式会社サニーマーケティング','サニーマーケティング','customer','810-0001','福岡県福岡市中央区天神3-6-7', '092-678-9012', 'info@sunnymarketing.jp',      '渡辺 六郎', 60],
        ];

        $clientModels = [];
        foreach ($clients as [$code, $name, $kana, $type, $postal, $address, $phone, $email, $contact, $terms]) {
            $clientModels[] = Client::firstOrCreate(['code' => $code], [
                'name'           => $name,
                'name_kana'      => $kana,
                'type'           => $type,
                'postal_code'    => $postal,
                'address'        => $address,
                'phone'          => $phone,
                'email'          => $email,
                'contact_person' => $contact,
                'payment_terms'  => $terms,
                'is_active'      => true,
            ]);
        }

        // 勘定科目取得
        $acSales    = AccountItem::where('code','4001')->first();
        $acCash     = AccountItem::where('code','1001')->first();
        $acBank     = AccountItem::where('code','1101')->first();
        $acAR       = AccountItem::where('code','1201')->first();  // 売掛金
        $acAP       = AccountItem::where('code','2001')->first();  // 買掛金
        $acTransport= AccountItem::where('code','5501')->first();  // 旅費交通費
        $acEntertain= AccountItem::where('code','5601')->first();  // 接待交際費
        $acSupplies = AccountItem::where('code','5701')->first();  // 消耗品費
        $acRent     = AccountItem::where('code','5201')->first();  // 地代家賃
        $acSalary   = AccountItem::where('code','5101')->first();  // 給料手当
        $acAP2      = AccountItem::where('code','2101')->first();  // 未払費用

        // ── 売上 ──────────────────────────────────────
        $salesData = [
            [$clientModels[0], '2026-01-15', 'システム開発費（1月分）',   500000, 'invoiced'],
            [$clientModels[0], '2026-02-15', 'システム保守費（2月分）',   150000, 'invoiced'],
            [$clientModels[1], '2026-02-20', 'Webデザイン制作費',          300000, 'paid'],
            [$clientModels[2], '2026-03-01', 'コンサルティング費（3月）',  200000, 'paid'],
            [$clientModels[2], '2026-03-28', 'コンサルティング費（追加）', 100000, 'invoiced'],
            [$clientModels[4], '2026-04-10', '研修サービス提供費',         450000, 'paid'],
            [$clientModels[5], '2026-04-25', 'マーケティング支援費',       380000, 'invoiced'],
            [$clientModels[0], '2026-05-15', 'システム開発費（5月分）',    600000, 'pending'],
            [$clientModels[1], '2026-05-30', 'ランディングページ制作',     250000, 'pending'],
            [$clientModels[3], '2026-06-05', 'セキュリティ診断費',         180000, 'invoiced'],
        ];

        $saleSeq = 1;
        $saleModels = [];
        foreach ($salesData as [$client, $date, $desc, $subtotal, $status]) {
            $tax   = (int)round($subtotal * 0.1);
            $total = $subtotal + $tax;
            $num   = 'S' . str_replace('-','',$date) . str_pad($saleSeq++,3,'0',STR_PAD_LEFT);

            $sale = Sale::firstOrCreate(['sale_number' => $num], [
                'client_id'    => $client->id,
                'user_id'      => $admin->id,
                'sale_date'    => $date,
                'description'  => $desc,
                'subtotal'     => $subtotal,
                'tax_amount'   => $tax,
                'total_amount' => $total,
                'tax_rate'     => '10',
                'status'       => $status,
            ]);

            SaleItem::firstOrCreate(['sale_id' => $sale->id, 'sort_order' => 0], [
                'item_name'  => $desc,
                'unit_price' => $subtotal,
                'quantity'   => 1,
                'unit'       => '式',
                'amount'     => $subtotal,
            ]);

            $saleModels[] = $sale;
        }

        // ── 請求書 ────────────────────────────────────
        $invoiceData = [
            [$clientModels[0], '2026-01-31', '2026-02-28', 500000, 'sent',    $saleModels[0]],
            [$clientModels[0], '2026-02-28', '2026-03-31', 150000, 'paid',    $saleModels[1]],
            [$clientModels[1], '2026-02-28', '2026-03-31', 300000, 'paid',    $saleModels[2]],
            [$clientModels[2], '2026-03-31', '2026-04-30', 200000, 'paid',    $saleModels[3]],
            [$clientModels[2], '2026-04-15', '2026-05-15', 100000, 'sent',    $saleModels[4]],
            [$clientModels[4], '2026-04-30', '2026-05-31', 450000, 'paid',    $saleModels[5]],
            [$clientModels[5], '2026-05-15', '2026-06-15', 380000, 'sent',    $saleModels[6]],
            [$clientModels[3], '2026-06-10', '2026-07-10', 180000, 'draft',   $saleModels[9]],
        ];

        $invSeq = 1;
        $invoiceModels = [];
        foreach ($invoiceData as [$client, $iDate, $dDate, $subtotal, $status, $sale]) {
            $tax   = (int)round($subtotal * 0.1);
            $total = $subtotal + $tax;
            $num   = 'INV-' . str_replace('-','',substr($iDate,0,7)) . '-' . str_pad($invSeq++,3,'0',STR_PAD_LEFT);

            $inv = Invoice::firstOrCreate(['invoice_number' => $num], [
                'client_id'      => $client->id,
                'user_id'        => $admin->id,
                'invoice_date'   => $iDate,
                'due_date'       => $dDate,
                'subtotal'       => $subtotal,
                'tax_amount'     => $tax,
                'total_amount'   => $total,
                'paid_amount'    => $status === 'paid' ? $total : 0,
                'status'         => $status,
                'sent_at'        => in_array($status,['sent','paid']) ? $iDate . ' 09:00:00' : null,
            ]);

            InvoiceItem::firstOrCreate(['invoice_id' => $inv->id, 'sort_order' => 0], [
                'item_name'  => $sale->description,
                'unit_price' => $subtotal,
                'quantity'   => 1,
                'unit'       => '式',
                'amount'     => $subtotal,
                'tax_rate'   => '10',
            ]);

            $invoiceModels[] = $inv;
        }

        // ── 入金 ──────────────────────────────────────
        $receiptData = [
            [$clientModels[0], '2026-03-25', 165000, $invoiceModels[1], 'bank_transfer', 'みずほ銀行'],
            [$clientModels[1], '2026-03-28', 330000, $invoiceModels[2], 'bank_transfer', '三井住友銀行'],
            [$clientModels[2], '2026-04-25', 220000, $invoiceModels[3], 'bank_transfer', '三菱UFJ銀行'],
            [$clientModels[4], '2026-05-28', 495000, $invoiceModels[5], 'bank_transfer', 'りそな銀行'],
        ];

        $rcpSeq = 1;
        foreach ($receiptData as [$client, $date, $amount, $invoice, $method, $bank]) {
            Receipt::firstOrCreate(['receipt_number' => 'RCP-' . str_pad($rcpSeq,6,'0',STR_PAD_LEFT)], [
                'client_id'    => $client->id,
                'invoice_id'   => $invoice->id,
                'user_id'      => $admin->id,
                'receipt_date' => $date,
                'amount'       => $amount,
                'method'       => $method,
                'bank_name'    => $bank,
                'account_number' => '1234567',
            ]);
            $rcpSeq++;
        }

        // ── 支払 ──────────────────────────────────────
        $paymentData = [
            [$clientModels[3], '2026-04-30', '2026-04-30', 132000,  '外注費（4月）',    'paid',    $acAP],
            [$clientModels[3], '2026-05-31', null,          88000,   '外注費（5月）',    'approved',$acAP],
            [$clientModels[3], '2026-06-15', null,          264000,  'セキュリティ診断費','pending', $acAP],
        ];

        $paySeq = 1;
        foreach ($paymentData as [$client, $due, $paid, $amount, $desc, $status, $acItem]) {
            Payment::firstOrCreate(['payment_number' => 'PAY-' . str_pad($paySeq,6,'0',STR_PAD_LEFT)], [
                'client_id'      => $client->id,
                'user_id'        => $admin->id,
                'due_date'       => $due,
                'payment_date'   => $paid,
                'amount'         => $amount,
                'method'         => 'bank_transfer',
                'description'    => $desc,
                'status'         => $status,
                'account_item_id'=> $acItem->id,
            ]);
            $paySeq++;
        }

        // ── 仕訳帳 ────────────────────────────────────
        $journalData = [
            ['2026-01-31', '1月売上計上（サンプルテック）', [
                ['debit',  $acAR,    550000, '売掛金'],
                ['credit', $acSales, 500000, '売上高'],
                ['credit', $acAP2,    50000, '仮受消費税'],
            ]],
            ['2026-02-28', '2月売上入金（フューチャーデザイン）', [
                ['debit',  $acBank, 330000, '普通預金'],
                ['credit', $acAR,   330000, '売掛金消込'],
            ]],
            ['2026-03-31', '3月給与支払', [
                ['debit',  $acSalary, 400000, '給料手当'],
                ['credit', $acBank,   400000, '普通預金'],
            ]],
            ['2026-04-30', '4月地代家賃支払', [
                ['debit',  $acRent, 150000, '地代家賃'],
                ['credit', $acBank, 150000, '普通預金'],
            ]],
            ['2026-05-31', '5月経費精算（交通費）', [
                ['debit',  $acTransport, 25000, '旅費交通費'],
                ['credit', $acCash,      25000, '現金'],
            ]],
        ];

        $jSeq = 1;
        foreach ($journalData as [$date, $desc, $entries]) {
            $num = 'J' . str_replace('-','',$date) . str_pad($jSeq++,3,'0',STR_PAD_LEFT);
            $journal = Journal::firstOrCreate(['journal_number' => $num], [
                'journal_date' => $date,
                'description'  => $desc,
                'source_type'  => 'manual',
                'user_id'      => $admin->id,
            ]);

            foreach ($entries as $i => [$side, $acItem, $amount, $entryDesc]) {
                JournalEntry::firstOrCreate(['journal_id' => $journal->id, 'sort_order' => $i], [
                    'side'           => $side,
                    'account_item_id'=> $acItem->id,
                    'amount'         => $amount,
                    'description'    => $entryDesc,
                ]);
            }
        }

        // ── 経費精算 ──────────────────────────────────
        $expenseData = [
            [$staff,  '2026-04-01', '4月交通費精算', 'approved', [
                [$acTransport, '2026-04-02', '電車代（渋谷→新宿）',  580],
                [$acTransport, '2026-04-05', '新幹線（東京→大阪）', 14800],
                [$acTransport, '2026-04-08', 'タクシー代',           3200],
            ]],
            [$staff,  '2026-05-10', '5月接待交際費',  'approved', [
                [$acEntertain, '2026-05-12', '顧客との会食（サンプルテック）', 32000],
                [$acEntertain, '2026-05-15', '手土産代',                        5400],
            ]],
            [$admin,  '2026-05-20', '消耗品購入',     'paid', [
                [$acSupplies, '2026-05-20', 'コピー用紙・文具',    4800],
                [$acSupplies, '2026-05-20', 'トナーカートリッジ', 12000],
            ]],
            [$staff,  '2026-06-01', '6月出張費',      'pending', [
                [$acTransport, '2026-06-03', '新幹線（東京→名古屋）', 11440],
                [$acTransport, '2026-06-03', 'ホテル宿泊費',          12000],
                [$acEntertain, '2026-06-03', '懇親会費',               8000],
            ]],
            [$staff,  '2026-06-15', '研修参加費精算', 'pending', [
                [$acSupplies, '2026-06-15', 'オンライン研修受講料', 30000],
            ]],
        ];

        $expSeq = 1;
        foreach ($expenseData as [$user, $date, $title, $status, $items]) {
            $total = array_sum(array_column($items, 3));
            $num   = 'EXP-' . str_pad($expSeq,6,'0',STR_PAD_LEFT);

            $expense = Expense::firstOrCreate(['expense_number' => $num], [
                'user_id'       => $user->id,
                'approved_by'   => in_array($status,['approved','paid']) ? $admin->id : null,
                'expense_date'  => $date,
                'applied_date'  => $date,
                'title'         => $title,
                'total_amount'  => $total,
                'status'        => $status,
                'approved_at'   => in_array($status,['approved','paid']) ? now()->toDateTimeString() : null,
            ]);

            foreach ($items as $i => [$acItem, $itemDate, $itemDesc, $amount]) {
                ExpenseItem::firstOrCreate(['expense_id' => $expense->id, 'sort_order' => $i], [
                    'account_item_id' => $acItem->id,
                    'item_date'       => $itemDate,
                    'description'     => $itemDesc,
                    'amount'          => $amount,
                    'tax_rate'        => '10',
                ]);
            }
            $expSeq++;
        }

        $this->command->info('✅ テストデータを投入しました。');
        $this->command->info('   取引先: 6件 / 売上: 10件 / 請求書: 8件 / 入金: 4件');
        $this->command->info('   支払: 3件 / 仕訳: 5件 / 経費精算: 5件');
    }
}
