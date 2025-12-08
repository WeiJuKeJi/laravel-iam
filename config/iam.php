<?php

return [
    'guard' => 'sanctum',

    'route_prefixes' => [
        'iam',
        'mdm',
        'ordersys',
        'qmp',
        'finance',
        'datahub',
        'yjf',
    ],

    'ignore_routes' => [
        'iam.auth.login',
        'iam.auth.logout',
        'iam.auth.me',
        'iam.routes.index',
        'api.iam.auth.login',
        'api.iam.auth.logout',
        'api.iam.auth.me',
        'api.iam.routes.index',
    ],

    'action_map' => [
        'index' => 'view',
        'show' => 'view',
        'store' => 'manage',
        'update' => 'manage',
        'destroy' => 'manage',
        'create' => 'manage',
        'edit' => 'manage',
        // MDM 自定义动作
        'by-nsrsbh' => 'view',
        'children' => 'view',
        'by-company' => 'view',
        'valid-config' => 'view',
        'set-as-default' => 'manage',
        'leave' => 'manage',
        'default-operator' => 'view',
    ],

    'action_labels' => [
        'view' => '查看',
        'manage' => '管理',
        'assign' => '分配',
        'revoke' => '撤销',
        'export' => '导出',
    ],

    'group_labels' => [
        'iam.users' => 'iam.用户',
        'iam.roles' => 'iam.角色',
        'iam.permissions' => 'iam.权限',
        'iam.menus' => 'iam.菜单',
        'iam.routes' => 'iam.路由',
        'mdm.companies' => 'mdm.企业',
        'mdm.company-tax-configs' => 'mdm.税务配置',
        'mdm.company-operators' => 'mdm.开票员',
        'mdm.projects' => 'mdm.项目',
        'mdm.payment-channels' => 'mdm.支付渠道',
        'ordersys.tenants' => 'ordersys.租户',
        'qmp.orders' => 'qmp.订单',
        'qmp.order-items' => 'qmp.订单明细',
        'qmp.order-tickets' => 'qmp.票券',
        'qmp.payments' => 'qmp.支付',
        'qmp.pay-transactions' => 'qmp.支付流水',
        'qmp.refunds' => 'qmp.退款',
        'finance.finances' => 'finance.财务',
        'finance.bill-downloads' => 'finance.账单下载',
        'datahub.datahubs' => 'datahub.数据中台',
        'yjf.yjfs' => 'yjf.账单',
        'projects' => 'common.项目',
    ],

    'sync_roles' => [
        'super-admin',
        'Admin',
    ],
];
