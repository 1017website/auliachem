<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── Customers ──
        DB::table('customers')->insert([
            ['company_name'=>'PT. Maju Kimia Indonesia','pic_name'=>'Budi Santoso','pic_position'=>'Purchasing Manager','phone'=>'0812-1111-2222','email'=>'budi@majukimia.co.id','address'=>'Jl. Industri Raya No. 10, Surabaya','industry'=>'Manufacturing','location'=>'Surabaya','status'=>'Existing','value_tag'=>'High Value','user_id'=>1,'customer_since'=>'2022-03-15','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'PT. Cipta Resin Abadi','pic_name'=>'Andi Wijaya','pic_position'=>'Procurement','phone'=>'0813-2222-3333','email'=>'andi@ciptaresin.co.id','address'=>'Jl. Raya Bekasi No. 45, Jakarta Timur','industry'=>'Plastic & Packaging','location'=>'Jakarta','status'=>'Existing','value_tag'=>'High Value','user_id'=>2,'customer_since'=>'2021-07-20','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'CV. Solvent Jaya','pic_name'=>'Dewi Rahayu','pic_position'=>'Owner','phone'=>'0811-3333-4444','email'=>'dewi@solventjaya.co.id','address'=>'Jl. Gatot Subroto No. 88, Bandung','industry'=>'Coating & Paint','location'=>'Bandung','status'=>'Existing','value_tag'=>'Normal','user_id'=>1,'customer_since'=>'2023-01-10','created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'PT. Global Pigment Nusantara','pic_name'=>'Hendra Kusuma','pic_position'=>'Direktur','phone'=>'0819-4444-5555','email'=>'hendra@globalpigment.co.id','address'=>'Kawasan Industri MM2100, Bekasi','industry'=>'Ink & Pigment','location'=>'Bekasi','status'=>'Potential','value_tag'=>'High Value','user_id'=>2,'customer_since'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['company_name'=>'PT. Surya Polimer Mandiri','pic_name'=>'Fajar Nugroho','pic_position'=>'Technical Manager','phone'=>'0812-5555-6666','email'=>'fajar@suryapolimer.co.id','address'=>'Jl. Raya Semarang No. 22, Semarang','industry'=>'Polymer & Rubber','location'=>'Semarang','status'=>'Potential','value_tag'=>'Normal','user_id'=>1,'customer_since'=>null,'created_at'=>$now,'updated_at'=>$now],
        ]);

        // ── Suppliers ──
        DB::table('suppliers')->insert([
            ['supplier_name'=>'PT. Bratachem','pic_name'=>'Rudi Hartono','pic_position'=>'Sales Manager','phone'=>'0812-6666-7777','email'=>'rudi@bratachem.co.id','address'=>'Jl. Karet Pedurenan No. 10, Jakarta','source_type'=>'Local','product_category'=>'Solvent, Resin, Pigment','origin_country'=>null,'status'=>'Active','relationship_status'=>'Existing','is_preferred'=>1,'rating'=>4.8,'payment_term'=>'30 Days','supplier_since'=>'2020-01-12','created_at'=>$now,'updated_at'=>$now],
            ['supplier_name'=>'Dow Chemical Indonesia','pic_name'=>'Jimmy Setiawan','pic_position'=>'Account Manager','phone'=>'0813-7777-8888','email'=>'jimmy@dow.com','address'=>'Gedung BRI II, Jakarta Pusat','source_type'=>'Import','product_category'=>'Polyurethane, Epoxy','origin_country'=>'USA','status'=>'Active','relationship_status'=>'Existing','is_preferred'=>1,'rating'=>4.7,'payment_term'=>'45 Days','supplier_since'=>'2019-08-15','created_at'=>$now,'updated_at'=>$now],
            ['supplier_name'=>'Sinopec Chemical Shanghai','pic_name'=>'Wang Li','pic_position'=>'Export Manager','phone'=>'0811-8888-9999','email'=>'wangli@sinopec.cn','address'=>'Shanghai, China','source_type'=>'Import','product_category'=>'Methanol, Ethanol, Toluene','origin_country'=>'China','status'=>'Active','relationship_status'=>'Existing','is_preferred'=>0,'rating'=>4.5,'payment_term'=>'60 Days','supplier_since'=>'2021-03-08','created_at'=>$now,'updated_at'=>$now],
            ['supplier_name'=>'PT. Chandra Asri Petrochemical','pic_name'=>'Sari Dewi','pic_position'=>'Sales Executive','phone'=>'0812-9090-1010','email'=>'sari@cap.co.id','address'=>'Jl. Raya Anyer KM 123, Cilegon','source_type'=>'Local','product_category'=>'Polyethylene, Polypropylene','origin_country'=>null,'status'=>'Active','relationship_status'=>'Existing','is_preferred'=>0,'rating'=>4.3,'payment_term'=>'30 Days','supplier_since'=>'2022-06-01','created_at'=>$now,'updated_at'=>$now],
            ['supplier_name'=>'BASF India Limited','pic_name'=>'Raj Sharma','pic_position'=>'Regional Sales','phone'=>'0813-1010-2020','email'=>'raj.sharma@basf.in','address'=>'Mumbai, India','source_type'=>'Import','product_category'=>'Specialty Chemical, Additives','origin_country'=>'India','status'=>'Active','relationship_status'=>'Potential','is_preferred'=>0,'rating'=>0,'payment_term'=>null,'supplier_since'=>null,'created_at'=>$now,'updated_at'=>$now],
        ]);

        // ── Leads ──
        $leads = [
            ['lead_code'=>'LEAD-2025-0001','company_name'=>'PT. Maju Kimia Indonesia','pic_name'=>'Budi Santoso','phone'=>'0812-1111-2222','industry'=>'Manufacturing','pipeline_stage'=>'Identifying','temperature'=>'Warm','product_interest'=>'Solvent IPA','volume_estimate'=>'5 Ton/Bulan','potensi_revenue'=>75000000,'probability'=>20,'lead_score'=>45,'lead_source'=>'Website','user_id'=>1,'customer_id'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['lead_code'=>'LEAD-2025-0002','company_name'=>'CV. Solvent Jaya','pic_name'=>'Dewi Rahayu','phone'=>'0811-3333-4444','industry'=>'Coating & Paint','pipeline_stage'=>'Approaching','temperature'=>'Hot','product_interest'=>'Toluene & Xylene','volume_estimate'=>'10 Ton/Bulan','potensi_revenue'=>120000000,'probability'=>40,'lead_score'=>65,'lead_source'=>'Referral','user_id'=>2,'customer_id'=>3,'created_at'=>$now,'updated_at'=>$now],
            ['lead_code'=>'LEAD-2025-0003','company_name'=>'PT. Global Pigment Nusantara','pic_name'=>'Hendra Kusuma','phone'=>'0819-4444-5555','industry'=>'Ink & Pigment','pipeline_stage'=>'Follow Up','temperature'=>'Warm','product_interest'=>'Pigment Paste & Dispersant','volume_estimate'=>'2 Ton/Bulan','potensi_revenue'=>80000000,'probability'=>50,'lead_score'=>60,'lead_source'=>'Cold Call','user_id'=>1,'customer_id'=>4,'created_at'=>$now,'updated_at'=>$now],
            ['lead_code'=>'LEAD-2025-0004','company_name'=>'PT. Cipta Resin Abadi','pic_name'=>'Andi Wijaya','phone'=>'0813-2222-3333','industry'=>'Plastic & Packaging','pipeline_stage'=>'Closing','temperature'=>'Hot','product_interest'=>'Epoxy Resin','volume_estimate'=>'3 Ton/Bulan','potensi_revenue'=>200000000,'probability'=>75,'lead_score'=>82,'lead_source'=>'Referral','user_id'=>1,'customer_id'=>2,'notes_kebutuhan'=>'Butuh kualitas food grade dan sertifikasi COA.','competitor'=>'2 Supplier lain','expected_closing'=>'2025-06-30','next_follow_up'=>'2025-05-22','created_at'=>$now,'updated_at'=>$now],
            ['lead_code'=>'LEAD-2025-0005','company_name'=>'PT. Surya Polimer Mandiri','pic_name'=>'Fajar Nugroho','phone'=>'0812-5555-6666','industry'=>'Polymer & Rubber','pipeline_stage'=>'Won','temperature'=>'Hot','product_interest'=>'Polyethylene HDPE','volume_estimate'=>'20 Ton/Bulan','potensi_revenue'=>350000000,'probability'=>100,'lead_score'=>95,'lead_source'=>'Referral','user_id'=>2,'customer_id'=>5,'created_at'=>$now,'updated_at'=>$now],
        ];
        foreach ($leads as $lead) {
            DB::table('leads')->insert($lead);
        }

        // ── Purchase Orders + Items ──
        $pos = [
            ['po_number'=>'PO-202605-0001','customer_id'=>1,'supplier_id'=>1,'currency'=>'IDR','status'=>'Done','order_date'=>'2026-05-10'],
            ['po_number'=>'PO-202605-0002','customer_id'=>2,'supplier_id'=>2,'currency'=>'IDR','status'=>'Done','order_date'=>'2026-05-08'],
            ['po_number'=>'PO-202605-0003','customer_id'=>3,'supplier_id'=>3,'currency'=>'IDR','status'=>'In Progress','order_date'=>'2026-05-05'],
        ];
        $items = [
            [
                ['product_name'=>'IPA (Isopropyl Alcohol)','unit'=>'kg','qty'=>5000,'buy_price'=>12000,'sell_price'=>15000],
                ['product_name'=>'Ethanol 96%','unit'=>'liter','qty'=>2000,'buy_price'=>8000,'sell_price'=>10500],
            ],
            [
                ['product_name'=>'Epoxy Resin Bisphenol A','unit'=>'kg','qty'=>3000,'buy_price'=>35000,'sell_price'=>43000],
            ],
            [
                ['product_name'=>'Toluene','unit'=>'kg','qty'=>8000,'buy_price'=>9500,'sell_price'=>12000],
                ['product_name'=>'Xylene','unit'=>'kg','qty'=>5000,'buy_price'=>10000,'sell_price'=>12500],
            ],
        ];
        foreach ($pos as $i => $po) {
            $poId = DB::table('purchase_orders')->insertGetId(array_merge($po, ['lead_id'=>null,'notes'=>null,'created_at'=>$now,'updated_at'=>$now]));
            foreach ($items[$i] as $item) {
                DB::table('purchase_order_items')->insert(array_merge($item, ['purchase_order_id'=>$poId,'description'=>null,'created_at'=>$now,'updated_at'=>$now]));
            }
        }
    }
}
