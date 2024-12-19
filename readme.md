# woocommerce-czlexpress

## 描述

1. 配置信息写入到mysql
2. order_id, tracking_number, label_url, 写入到mysql
3. 适配PHP7和PHP8
4. 功能嵌入到woocommerce内, 通过woocommerce的钩子函数, 实现功能
5. 查价运费是人民币, 支持按汇率转换为其他货币,主要是USD

## 系统要求

1. WordPress 6.0+
2. WooCommerce 6.0.0+
3. PHP 7.4+
4. MySQL 5.6+

## 特性

1. 完全支持WooCommerce高性能订单存储(HPOS)
2. 支持WooCommerce远程日志记录
3. 支持多语言

## 使用步骤

1. 安装本插件并启用
2. 在插件-基本设置里, 配置CZLExpress的账号密码, 以及汇率
3. 在WooCommerce的设置里, 选择'Shipping', 配置"Shipping zones", 新建一个"Zone", 地区选择全部, Shipping method选择"CZL Express", 进行配置, 然后保存 Zone.
4. 在"CZL Express"-"产品分组" 里, 配置产品分组, 可以删除默认分组数据, 然后添加自定义分组, 例如: "SF Line","顺丰小包".
5. 当客户下单时, 输入地址信息后, 会自动计算运费并显示, 提供给客户选择. 下单后, 运输信息会显示在"Orders"-"Edit order"里, 信息示例: 
  ``` 
	UPS Saver (3-6 working days) 
	product_id:	10381
	delivery_time:	3-6个工作日
	original_name:	UPS 红单-T价
	is_group:	1
	group_name:	UPS Saver
	original_amount:	747
  ``` 
6. 然后, 可以在"CZL Express"-"订单管理"里, 进行"创建运单", 会自动下单到CZL Express, 成功后可以"打印标签"
7. 每半个小时会自动同步订单的跟踪单号(如果有变更). 也可以手动更改跟踪单号.
8. 每一个小时会自动同步订单运输轨迹, 并且客户可以在订单详情页看到运输轨迹. 也支持点击"更新轨迹"进行手动更新.

## 功能
1. 用户可以选择按产品分组映射woocommerce的运输方式. 然后客户下单时就会自动显示每种运输方式的运费.

2. 设置运输价格时, 可以设置在CZLExpress的运费上额外加上一定比例和固定金额, 支持表达式, 例如"10% + 10", 那么就是CZLExpress的运费乘以1.1, 然后加上10元. 

3. 支持设置账号密码, 然后在woocommerce订单列表, 支持快捷下单到CZLExpress, 如果下单成功, 支持打印标签.

4. 在插件的"订单管理"里, 显示CZLExpress的订单跟踪号, 并且可以点击跟踪号, 跳转到CZLExpress的订单跟踪页面. 

5. 可以把订单运输轨迹, 显示在woocommerce的订单公开备注里, 客户可以看到.


