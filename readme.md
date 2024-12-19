# woocommerce-czlexpress

## 描述

1. 配置信息写入到mysql
2. order_id, tracking_number, label_url, 写入到mysql
3. 适配PHP7和PHP8
4. 功能嵌入到woocommerce内, 通过woocommerce的钩子函数, 实现功能
5. 查价运费是人民币, 支持按汇率转换为其他货币,主要是USD

## 系统要求

1. WordPress 5.0+
2. WooCommerce 6.0.0+
3. PHP 7.0+
4. MySQL 5.6+

## 特性

1. 完全支持WooCommerce高性能订单存储(HPOS)
2. 支持WooCommerce远程日志记录
3. 支持多语言

## 功能
1. 支持快捷查价, 然后映射产品和woocommerce的运输方式. 其中, 查价结果里, 有一些产品, 比如 "UPS 红单-TA价", "UPS 红单-DA55价", 这种以"UPS 红单"开头的产品. 还有比如: "FEDEX IE-DT价", "FEDEX IE-DE价", 这种以"FEDEX IE"开头的产品. 我希望在查价结果里, 把他们显示为一个类别, 比如"UPS 红单", 然后这一行只显示最便宜的一个"UPS 红单"开头的产品价格和时效. 然后这个类别可以点击展开, 下面一个列表显示所有以"UPS 红单"开头的具体产品和相关信息. 并且可以自定义类别名称和产品开头, 例如这个配置: 
  productGroupConfig: {
					'UPS 蓝单': {
						prefixes: ['UPS 蓝单'],
						groupName: 'UPS 蓝单'
					},
					'UPS 红单': {
						prefixes: ['UPS 红单'],
						groupName: 'UPS 红单'
					},
					"江苏FEDEX IE": {
						prefixes: ["江苏FEDEX IE"],
						groupName: "江苏FEDEX IE"
					},
					"江苏FEDEX IP": {
						prefixes: ["江苏FEDEX IP"],
						groupName: "江苏FEDEX IP"
					},
					'FEDEX IE': {
						prefixes: ['FEDEX IE'],
						groupName: 'FEDEX IE'
					},
					'FEDEX IP': {
						prefixes: ['FEDEX IP'],
						groupName: 'FEDEX IP'
					},
					"DHL": {
						prefixes: ["DHL"],
						groupName: "DHL"
					},
					"欧美普货包税专线": {
						prefixes: ["欧美经济专线(普货)","欧美标准专线(普货)","欧洲经济专线(普货)","欧洲标准专线(普货)"],
						groupName: "欧美普货包税专线"
					},
					"欧美B类包税专线": {
						prefixes: ["欧美经济专线(B类)","欧美标准专线(B类)","欧洲经济专线(B类)","欧洲标准专线(B类)"],
						groupName: "欧美B类包税专线"
					},
					"欧美带电包税专线": {
						prefixes: ["欧美经济专线(带电)","欧美标准专线(带电)","欧洲经济专线(带电)","欧洲标准专线(带电)"],
						groupName: "欧美带电包税专线"
					},
					"迪拜DHL": {
						prefixes: ["迪拜DHL"],
						groupName: "迪拜DHL"
					},
					"迪拜UPS": {
						prefixes: ["迪拜UPS"],
						groupName: "迪拜UPS"
					},
					"迪拜FEDEX": {
						prefixes: ["迪拜FEDEX"],
						groupName: "迪拜FEDEX"
					},
					"邮政": {
						prefixes: ["E特快","EMS"],
						groupName: "邮政"
					},
					"CZL阿联酋专线": {
						prefixes: ["CZL阿联酋"],
						groupName: "CZL阿联酋专线"
					},
				}

  用户可以选择按产品分组映射woocommerce的运输方式. 也可以选择具体产品映射woocommerce的运输方式. 

2. 设置运输价格时, 可以设置在CZLExpress的运费上额外加上一定比例和固定金额, 支持表达式, 例如"10% + 10", 那么就是CZLExpress的运费乘以1.1, 然后加上10元. 

3. 支持设置账号密码, 然后在woocommerce订单列表, 支持快捷下单到CZLExpress, 如果下单无误, 支持打印标签,如果下单有问题, 显示报错内容, 可以手动修改, 然后打印标签. 

4. 在woocommerce的订单详情页, 显示CZLExpress的订单跟踪号, 并且可以点击跟踪号, 跳转到CZLExpress的订单跟踪页面. 

5. 在woocommerce的订单详情页, 显示CZLExpress的订单标签, 并且可以点击标签, 跳转到CZLExpress的订单标签下载页面. 

6. 可以把订单运输轨迹, 显示在woocommerce的订单详情页. 

7. 当用户在woocommerce下单时, 自动查询偏远和偏远费用, 然后加到运输费用里. 并且也单独显示偏远和偏远费用. 


