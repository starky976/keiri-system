<?php

namespace Database\Seeders;

use App\Models\AccountItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name'     => '管理者',
            'password' => Hash::make('password'),
        ]);

        $items = [
            ['1001','現金','asset','current_asset',1],
            ['1101','普通預金','asset','current_asset',2],
            ['1201','売掛金','asset','current_asset',3],
            ['1301','商品','asset','current_asset',4],
            ['1501','建物','asset','fixed_asset',5],
            ['1701','備品','asset','fixed_asset',6],
            ['2001','買掛金','liability','current_liability',10],
            ['2101','未払費用','liability','current_liability',11],
            ['2201','前受金','liability','current_liability',12],
            ['2401','長期借入金','liability','fixed_liability',13],
            ['3001','資本金','equity','equity',15],
            ['3101','繰越利益剰余金','equity','equity',16],
            ['4001','売上高','revenue','operating_revenue',17],
            ['4101','受取利息','revenue','non_operating_revenue',18],
            ['4201','雑収入','revenue','non_operating_revenue',19],
            ['5001','仕入高','expense','operating_expense',20],
            ['5101','給料手当','expense','operating_expense',21],
            ['5201','地代家賃','expense','operating_expense',22],
            ['5301','水道光熱費','expense','operating_expense',23],
            ['5401','通信費','expense','operating_expense',24],
            ['5501','旅費交通費','expense','operating_expense',25],
            ['5601','接待交際費','expense','operating_expense',26],
            ['5701','消耗品費','expense','operating_expense',27],
            ['5801','広告宣伝費','expense','operating_expense',28],
            ['6001','支払手数料','expense','operating_expense',29],
            ['6101','支払利息','expense','non_operating_expense',30],
            ['6201','雑費','expense','operating_expense',31],
        ];

        foreach ($items as [$code, $name, $category, $sub, $sort]) {
            AccountItem::firstOrCreate(['code' => $code], [
                'name'         => $name,
                'category'     => $category,
                'sub_category' => $sub,
                'sort_order'   => $sort,
                'is_active'    => true,
            ]);
        }

        // テストデータが必要な場合は以下を呼び出す
        // $this->call(TestDataSeeder::class);
    }
}
