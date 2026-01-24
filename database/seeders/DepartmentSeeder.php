<?php

namespace WeiJuKeJi\LaravelIam\Database\Seeders;

use Illuminate\Database\Seeder;
use WeiJuKeJi\LaravelIam\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 清空现有数据
        Department::query()->delete();

        // 创建景区总部
        $head = Department::create([
            'name' => '景区总部',
            'code' => 'HEAD',
            'sort_order' => 0,
            'status' => 'active',
            'description' => '景区管理总部',
        ]);

        // 票务运营部
        $ticketing = Department::create([
            'parent_id' => $head->id,
            'name' => '票务运营部',
            'code' => 'TICKET',
            'sort_order' => 1,
            'status' => 'active',
            'description' => '负责景区票务销售和运营管理',
        ]);

        Department::create([
            'parent_id' => $ticketing->id,
            'name' => '售票中心',
            'code' => 'TICKET-SALES',
            'sort_order' => 1,
            'status' => 'active',
            'description' => '现场售票窗口',
        ]);

        Department::create([
            'parent_id' => $ticketing->id,
            'name' => '检票中心',
            'code' => 'TICKET-CHECK',
            'sort_order' => 2,
            'status' => 'active',
            'description' => '景区检票入口管理',
        ]);

        Department::create([
            'parent_id' => $ticketing->id,
            'name' => '线上票务',
            'code' => 'TICKET-ONLINE',
            'sort_order' => 3,
            'status' => 'active',
            'description' => 'OTA及线上渠道票务',
        ]);

        // 客户服务部
        $service = Department::create([
            'parent_id' => $head->id,
            'name' => '客户服务部',
            'code' => 'SERVICE',
            'sort_order' => 2,
            'status' => 'active',
            'description' => '游客接待与服务',
        ]);

        Department::create([
            'parent_id' => $service->id,
            'name' => '游客接待',
            'code' => 'SERVICE-RECEPTION',
            'sort_order' => 1,
            'status' => 'active',
            'description' => '游客咨询与接待',
        ]);

        Department::create([
            'parent_id' => $service->id,
            'name' => '投诉处理',
            'code' => 'SERVICE-COMPLAINT',
            'sort_order' => 2,
            'status' => 'active',
            'description' => '游客投诉与意见处理',
        ]);

        Department::create([
            'parent_id' => $service->id,
            'name' => '导游服务',
            'code' => 'SERVICE-GUIDE',
            'sort_order' => 3,
            'status' => 'active',
            'description' => '导游讲解服务',
        ]);

        // 市场营销部
        $marketing = Department::create([
            'parent_id' => $head->id,
            'name' => '市场营销部',
            'code' => 'MARKET',
            'sort_order' => 3,
            'status' => 'active',
            'description' => '市场推广与渠道合作',
        ]);

        Department::create([
            'parent_id' => $marketing->id,
            'name' => '线上推广',
            'code' => 'MARKET-ONLINE',
            'sort_order' => 1,
            'status' => 'active',
            'description' => '新媒体及数字营销',
        ]);

        Department::create([
            'parent_id' => $marketing->id,
            'name' => '渠道合作',
            'code' => 'MARKET-CHANNEL',
            'sort_order' => 2,
            'status' => 'active',
            'description' => '旅行社及OTA渠道合作',
        ]);

        // 设施维护部
        $facility = Department::create([
            'parent_id' => $head->id,
            'name' => '设施维护部',
            'code' => 'FACILITY',
            'sort_order' => 4,
            'status' => 'active',
            'description' => '景区设施设备维护',
        ]);

        Department::create([
            'parent_id' => $facility->id,
            'name' => '设备维护',
            'code' => 'FACILITY-EQUIP',
            'sort_order' => 1,
            'status' => 'active',
            'description' => '游乐设施及设备维护',
        ]);

        Department::create([
            'parent_id' => $facility->id,
            'name' => '环境保洁',
            'code' => 'FACILITY-CLEAN',
            'sort_order' => 2,
            'status' => 'active',
            'description' => '景区环境卫生保洁',
        ]);

        // 财务部
        Department::create([
            'parent_id' => $head->id,
            'name' => '财务部',
            'code' => 'FINANCE',
            'sort_order' => 5,
            'status' => 'active',
            'description' => '财务核算与资金管理',
        ]);

        // 人力资源部
        Department::create([
            'parent_id' => $head->id,
            'name' => '人力资源部',
            'code' => 'HR',
            'sort_order' => 6,
            'status' => 'active',
            'description' => '人力资源招聘与培训',
        ]);

        // IT信息部
        Department::create([
            'parent_id' => $head->id,
            'name' => 'IT信息部',
            'code' => 'IT',
            'sort_order' => 7,
            'status' => 'active',
            'description' => '信息系统开发与运维',
        ]);

        $this->command->info('景区部门数据填充完成！');
    }
}
