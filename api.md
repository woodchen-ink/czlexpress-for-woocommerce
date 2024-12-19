# API文档

## 国家接口
GET https://tms-api-go.czl.net/api/countries

返回: 
{"code":200,"msg":"success","data":[{"code":"AD","name":"安道尔","ename":"ANDORRA"},{"code":"AE","name":"阿拉伯联合酋长国","ename":"United Arab Emirates"},{"code":"AF","name":"阿富汗","ename":"Afghanistan"},{"code":"AG","name":"安提瓜岛","ename":"ANTIGUA AND BARBUDA"},{"code":"AI","name":"安圭拉","ename":"Anguilla"},{"code":"AL","name":"阿尔巴尼亚","ename":"Albania"},{"code":"AM","name":"亚美尼亚","ename":"Armenia"},{"code":"AN","name":"荷属安的列斯群岛","ename":"NETHERLANDS ANTILLES"},{"code":"AO","name":"安哥拉","ename":"Angola"},{"code":"AR","name":"阿根廷","ename":"Argentina"}...]}

## 查询偏远数据

GET https://tms-api-go.czl.net/api/remote/query

返回: 
{"dhl":{"is_remote":true,"remote_type":"","matched_city":"","matched_post":"082001"},"fedex":{"is_remote":true,"remote_type":"TIERB","matched_city":"","matched_post":"082001"},"tnt":{"is_remote":false,"remote_type":"","matched_city":"","matched_post":""},"ups":{"is_remote":false,"remote_type":"","matched_city":"","matched_post":""}}

## 偏远附加费等附加费数据

GET https://i-cf.czl.net/g-f/exp.czl.net/json/surcharges.json

返回: 
[{"name":"FEDEX A级偏远费最低收费","baseCharge":23,"courierType":"fedex","unit":"元/票","displayCategory":"偏远费","note":"按票计费"},{"name":"FEDEX B级偏远费单价","baseCharge":3.5,"courierType":"fedex","unit":"元/KG","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"FEDEX B级偏远费最低收费","baseCharge":168,"courierType":"fedex","unit":"元/票","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"FEDEX C级偏远费单价","baseCharge":4.6,"courierType":"fedex","unit":"元/KG","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"FEDEX C级偏远费最低收费","baseCharge":218,"courierType":"fedex","unit":"元/票","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"UPS偏远费单价","baseCharge":3.5,"courierType":"ups","unit":"元/KG","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"UPS偏远费最低收费","baseCharge":168,"courierType":"ups","unit":"元/票","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"DHL偏远费单价","baseCharge":4,"courierType":"dhl","unit":"元/KG","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"DHL偏远费最低收费","baseCharge":172,"courierType":"dhl","unit":"元/票","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"顺丰偏远费单价","baseCharge":3.5,"courierType":"0","unit":"元/KG","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"顺丰偏远费最低收费","baseCharge":50,"courierType":"0","unit":"元/票","displayCategory":"偏远费","note":"单价*计费重 与 最低收费, 取大计费"},{"name":"FEDEX基础附加费","baseCharge":195,"courierType":"fedex","unit":"元/件","displayCategory":"FEDEX附加费","note":"异形,最长边超1.2m,次边超76cm,单件超31KG,围长超266cm, 满足任意一条收取;并且将最低按18KG计费"},{"name":"FEDEX超大货件费","baseCharge":516,"courierType":"fedex","unit":"元/件","displayCategory":"FEDEX附加费","note":"最长边超243cm,周长超330cm,满足任意一条收取(最低18KG计费)"},{"name":"附加服务费 - 重货","baseCharge":1384,"courierType":"fedex","unit":"元/件","displayCategory":"FEDEX附加费","note":"此附加费适用于最长单边超过157厘米的单件重货处理(IEF和IPF)."},{"name":"更改地址","baseCharge":83,"courierType":"fedex","unit":"元/票","displayCategory":"FEDEX附加费","note":"如果收件人地址不完整(缺少门牌号等)或不正确，我们可能会尝试找到正确的地址并完成递送，并且我们将收取附加费。"},{"name":"住宅交付附加费","baseCharge":24,"courierType":"fedex","unit":"元/票","displayCategory":"FEDEX附加费","note":"（仅适用于运往美国和加拿大的货件）派送至住宅附加费适用于寄往家庭或私人住宅（包括在家中经营企业的地址）的货物，或寄件人将送货地址指定为住宅的任何货物。"},{"name":"指定清关代理人(最低)","baseCharge":96,"courierType":"fedex","unit":"元/票","displayCategory":"FEDEX附加费","note":"(两者取大)当发生以下所有情况时，将收取此附加费：代理（FedEx Express 或指定的FedEx Express以外的代理）负责清关货物。FedEx Express 在客户的代理地点取件并将其交付给最终收件人。收件人所在位置的清关点与代理的清关点不同。"},{"name":"指定清关代理人","baseCharge":10.7,"courierType":"fedex","unit":"元/KG","displayCategory":"FEDEX附加费","note":"(两者取大)当发生以下所有情况时，将收取此附加费：代理（FedEx Express 或指定的FedEx Express以外的代理）负责清关货物。FedEx Express 在客户的代理地点取件并将其交付给最终收件人。收件人所在位置的清关点与代理的清关点不同。"},{"name":"非堆叠货件附加费","baseCharge":1750,"courierType":"fedex","unit":"元/件","displayCategory":"FEDEX附加费","note":"国际优先快递重货服务（IPF）和国际经济快递重货服务（IEF）每件重货处理价为1,750 元人民币。此附加费适用于任何不可堆叠的重货货件处理，这意味着单件、滑橇或托盘不能以安全可靠的方式垂直堆叠。例如，包括但不限于以下不可堆叠的重货货件处理：1) 没有平坦且稳定的顶部或底座，2) 如果在此类重货上加上另一件包裹，则可能会损坏，或 3) 表面具有平坦且稳定的顶部或底座，但太窄而无法安全可靠地支撑其他单件重货货件（例如 1-2 个滚筒或鼓状物）。"},{"name":"UPS附加服务费","baseCharge":108,"courierType":"ups","unit":"元/件","displayCategory":"UPS附加费","note":"异形,最长边超1.2m,次边超76cm,平均单件超25KG,围长超300cm, 满足任意一条收取"},{"name":"UPS大型包裹附加费","baseCharge":514,"courierType":"ups","unit":"元/件","displayCategory":"UPS附加费","note":"当一个包裹的围长超过300cm, 但不超过UPS的最大尺寸400cm时，该包裹将被视为“大型包裹”。大型包裹的最小计费重量为40公斤"},{"name":"超重超长费","baseCharge":1938,"courierType":"ups","unit":"元/件","displayCategory":"UPS附加费","note":"单件超70KG,最长边超274cm,围长超400cm, 满足任意一条一般不接收. 如果接收了,收取此费用."},{"name":"更改地址","baseCharge":73,"courierType":"ups","unit":"元/件","displayCategory":"UPS附加费","note":"每更改收件人地址一次，如因地址不正确或因地址为邮政信箱无法递送时，此费用由寄件人支付。"},{"name":"更改地址(最高)","baseCharge":280,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"(按件计费,单票最高此费用)每更改收件人地址一次，如因地址不正确或因地址为邮政信箱无法递送时，此费用由寄件人支付。"},{"name":"住宅地址递送附加费","baseCharge":24,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"UPS 提供将货件递送至住宅地址和商务办公地址的服务。住宅地址包括私人住所及非公共办公场所，向此类地址递送货件每票需加收此费用。"},{"name":"海关查验费","baseCharge":150,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"当海关或相关部门需要查验进口或出口包裹货物时，每票货件加收查验费。此项收费用于承担 UPS 配合完成查验手续所产生的相关成本。"},{"name":"第三方清关代理费","baseCharge":127,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"收件人可以选择自己的清关代理进行 UPS 国际货件的海关清关。UPS 会将从托运人处收到的海关清关信息和文件转发给收件人或其指定的第三方清关代理。"},{"name":"寄件人支付目的地进口关税附加费","baseCharge":150,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"当选择“寄件人支付目的地进口关税 /税款”付款方式时，将向寄件人收取此附加费。"},{"name":"出口申报附加费","baseCharge":97,"courierType":"ups","unit":"元/票","displayCategory":"UPS附加费","note":"满足一条加收: (1)货件含所适用的当地法规所规定的策略性、受管控或监管的商品。(2)没达到强制正式报关要求(100KG或5000人民币)时，要求正式报关"},{"name":"违禁物品费","baseCharge":1038,"courierType":"ups","unit":"元/件","displayCategory":"UPS附加费","note":"UPS 将对每票交由 UPS 的违反或不符合适用法律法规，或不符合 UPS 服务或契约式服务条款和条件的货件（包括但不限于违禁货件或 UPS 无一例外不接受或不承运的货件，例如：危险货物和危险化学品）收取费用每件包裹 RMB1,038。此费用由发件人承担，且除了因违反或不遵守规定而对 UPS可能产生的所有其他费用之外，发件人还需承担相应的责任。"},{"name":"DHL超长费","baseCharge":155,"courierType":"dhl","unit":"元/件","displayCategory":"DHL附加费","note":"最长边超过100cm"},{"name":"DHL超重费","baseCharge":750,"courierType":"dhl","unit":"元/件","displayCategory":"DHL附加费","note":"单件实重或体积重超过70KG"},{"name":"DHL高风险地区附加费","baseCharge":230,"courierType":"dhl","unit":"元/票","displayCategory":"DHL附加费","note":"DHL在某些国家/地区运营具有高风险。 使用DHL发送或接收来自深受战争、内乱、恐怖主义困扰的高风险国家/地区的快件时所征收的附加费。"},{"name":"DHL限运目的地附加费","baseCharge":230,"courierType":"dhl","unit":"元/票","displayCategory":"DHL附加费","note":"快递服务需要遵从相关贸易限制 使用DHL发送或接收来自联合国安理会确定的贸易限运国家/地区的快件时所征收的附加费。"},{"name":"更正地址附加费","baseCharge":87,"courierType":"dhl","unit":"元/票","displayCategory":"DHL附加费","note":"由于运单上所填写的收件人地址信息不完整、过期或错误导致DHL无法完成派送时，DHL获取到正确地址以完成派送收取该项附加费。"},{"name":"无法搬运附加费","baseCharge":155,"courierType":"dhl","unit":"元/件","displayCategory":"DHL附加费","note":"对于每票快件中每个单件包裹，实际重量在25公斤至70公斤之间收取的附加费用。不适用于已经收取超重或超长附加费的货件。"},{"name":"无法堆叠托盘附加费","baseCharge":2340,"courierType":"dhl","unit":"元/件","displayCategory":"DHL附加费","note":"当您的托盘货物无法堆叠时 此附加费适用于由发件人提出要求或因形状、物品或包装材料导致无法堆叠的每个单件托盘货物。"}]

## 查价接口

POST https://tms.czl.net/defaultPriceSearchJson.htm?weight=重量&country=国家二字代码(例如US)&cargoType=P(P包裹，D文件)&length=10(长度)&width=10(宽度)&height=10(高度)&postcode=100000(邮编)


返回: 
[{"address_type":"","api_priceid":"","channelCode":"","channelName":"","channel_id":"","channel_note":"","charge_weight":"12.0","customer_code":"","customer_id":"","customer_shortname":"","customer_type":"","express_type":"顺丰","freight_amount":"","freight_priceId":"","incidental_priceId":"","is_api":"","oil_amount":"","old_freight_amount":"","old_freight_amount_currency":"","old_oil_amount":"","old_oil_amount_currency":"","otherFeeDetails":[],"other_amount":"","price_formula":"","price_groupname":"","price_inside_note":"","price_note":"","price_type":"","product_aging":"5-8个工作日","product_cargoattribute":"","product_id":"10601","product_name":"顺丰特惠-T价","product_note":"**周一到周六 13:00截单**\r\n\r\n**查看详细操作规定:**[https://l.czl.net/251](https://l.czl.net/251)\r\n\r\n**不接亚马逊件**\r\n\r\n顺丰保险费,申报金额的千分之八,最低收费20元,面签服务50元一票\r\n\r\n1. 转运了客户原因退回有上海顺丰同城双程退运费2/KG+20元手续费,国外退回有国际进口运费和进口关税,一律发件人支付\r\n2. 美国流向偏远地区费用3.7元一KG,最低收费50/票。马来西亚偏远费1.9元/kg\r\n3. 美国货物必须贴MADE IN CHINA,因原产地查验的货物,延误或者被退回(退回运费自付),我司概不负责\r\n4. 理赔标准;外包装完好货物损坏不作任何赔偿,丢失件需在24小时内反馈,超时反馈无法判断,丢失赔偿标准按照申报价值但最高赔偿金额不超过100美金加退运费华沙条款,价值较高请自行购买保险\r\n5. 价格未包含目的地清关税费、偏远地区附加费、更改地址等可能产生的其他费用,如因收件人原因导致拒付的一切费用,均需发件人承担!","product_tags":"","product_tracking":"","product_volumnrate":"5000","remote_type":"是","serveCode":"","serveName":"","serve_id":"","total_amount":"883","unitPriceAmount":"73.58","url":""},{"address_type":"","api_priceid":"","channelCode":"","channelName":"","channel_id":"","channel_note":"","charge_weight":"12.0","customer_code":"","customer_id":"","customer_shortname":"","customer_type":"","express_type":"UPS","freight_amount":"","freight_priceId":"","incidental_priceId":"","is_api":"","oil_amount":"","old_freight_amount":"","old_freight_amount_currency":"","old_oil_amount":"","old_oil_amount_currency":"","otherFeeDetails":[],"other_amount":"","price_formula":"","price_groupname":"","price_inside_note":"","price_note":"","price_type":"","product_aging":"3-6个工作日","product_cargoattribute":"","product_id":"11461","product_name":"UPS 红单-TA价","product_note":"**周一至周六 13:00截单;次日提取;免异形,免一般超长超重,需收方邮箱**\r\n\r\n**UPS附加费在我司官网查看**\r\n\r\nUPS袋子包裹计材积\r\n\r\n\r\n**附加费**\r\n\r\n2. 有单报关费100,买单报关费150元。\r\n3. 上网了客户原因退回的收取2/KG,最低20元一票。禁止拨打800客服,问题请发送我司客服处理\r\n4. 接磁检货,费用:1元/KG,单件最低30元,手续费30元/票\r\n5. 接FBA货物,如需关税预付DDP,手续费120元/票","product_tags":"","product_tracking":"","product_volumnrate":"5000","remote_type":"","serveCode":"","serveName":"","serve_id":"","total_amount":"918","unitPriceAmount":"76.50","url":""},{"address_type":"","api_priceid":"","channelCode":"","channelName":"","channel_id":"","channel_note":"","charge_weight":"12.000","customer_code":"","customer_id":"","customer_shortname":"","customer_type":"","express_type":"ZX","freight_amount":"","freight_priceId":"","incidental_priceId":"","is_api":"","oil_amount":"","old_freight_amount":"","old_freight_amount_currency":"","old_oil_amount":"","old_oil_amount_currency":"","otherFeeDetails":[],"other_amount":"","price_formula":"","price_groupname":"","price_inside_note":"","price_note":"","price_type":"","product_aging":"7-9个工作日","product_cargoattribute":"","product_id":"11641","product_name":"欧美标准专线(普货)-D","product_note":"**周一至周六 13:00截单, 双清包税,时效略慢**\r\n\r\n一、 派送到爱尔兰、罗马尼亚两个目的地国后可能发生二次清关,由此产生的清关费、杂费和税费等费用由收件人承担。\r\n\r\n二、 欧美UPS派送,支持一票多件及一票一件(美国只接收一票一件),一票多件时单件计费重最低11KG;\r\n\r\n**限制条件**  \r\n1. 单件实重<=20KG,计费重<=30KG,单票<=3件;\r\n2. 当单箱实重 W≥25kg时,按照200元/件收取;\r\n3. 长+宽x2+高x2<295cm;最长边<100cm,次长边<70cm;(超过无服务)\r\n\r\n三、 附加费:\r\n   - 磁检费\r\n   - 异形包装费:麻袋包装、木箱包装、铁箱包装、圆柱形包装、不规则包装,按照80元/件收取。\r\n   - 代操作费:需仓库代操作换箱,按照20元/件收取材料和操作费。\r\n   - 偏远附加费:5.5元/kg,最低270元/票;偏远费保留3个月追溯期。\r\n   - 部分偏远邮编会收取偏远附加费RMB55/件;部分超偏远邮编会收取偏远附加费RMB70/件;部分夏威夷邮编收取偏远费RMB95/件;外岛、夏威夷、阿拉斯加、军事基地等不可发货;","product_tags":"","product_tracking":"","product_volumnrate":"5000","remote_type":"","serveCode":"","serveName":"","serve_id":"","total_amount":"936","unitPriceAmount":"78.00","url":""},......]

## 查轨迹接口

POST https://tms.czl.net/selectTrack.htm?documentCode=(Order No/tracking NO)

返回: 

[{
"ack": "true",
"data": [
  {
    "consigneeCountry": "country code country code",
     "productKindName": "Logistics express type",
"referenceNumber": "reference number ref number",
"trackContent": "last track content",
"trackDate": "the last track date",
"trackLocation": "Shanghai the last track location",
"trackSignperson": "sign person, if no sign it's empty",
"trackingNumber": "tracking number tracking number",
"trackDetails": [
   {
"track_content":"track content",
"track_date":"track date",
"track_location":"Shanghai track location",
"track_signdate":"sign date,if no sign it's empty",
     "track_signperson":"sign person,if no sign it's empty"
    }
]
}
]
}]



## 查轨迹链接(自动跳转)

GET https://exp.czl.net/track/?query=(Order No/tracking NO)


## Get tracking number

This method can query the tracking number of an order. It is usually used after the system tracking number is modified or updated

POST https://tms.czl.net/getOrderTrackingNumber.htm?documentCode=orderNo

POST https://tms.czl.net/getOrderTrackingNumber.htm?order_id=order_id

## authentication

This is the identity authentication interface, which is used to obtain "customer ID" and "customer userid", which will be used in order creation

POST https://tms.czl.net/selectAuth.htm?username=TEST&password=123456

## create order(UTF-8)
This method can be create order . You can push the order data to CZL Express system to obtain the order tracking number and order ID for printing.

POST https://tms.czl.net/createOrderApi.htm

Request :
Parameter Name |	Data Type |	REQUIRED |	Description
Param |	string |	Require |	Json param

### Param:
{
   "buyerid": "",
   "consignee_address": "Receipt address street, required",
   "order_piece": "Number of pieces, small package default 1, express delivery must be filled in",
   "consignee_city" : "City",
   "consignee_mobile": "Mobile number, optional. To facilitate delivery, it is best to fill in",
   "order_returnsign": "Return sign, default N means no return, Y sign means return. China Post can ignore this attribute" ,
   "consignee_name": "Recipient, required",
   "trade_type": "ZYXT",
   "consignee_postcode": "Zip code, country with post code is required",
   "consignee_state": "State / Province",
   "consignee_telephone" : "Receiving phone number, required",
   "country": "Receipt country code, required",
   "customer_id": "Customer ID, required",
   "customer_userid": "Login ID, required" ,
   "orderInvoiceParam": [
       {
           "invoice_amount": "Declared total value, required",
           "invoice_pcs": "Number of pieces, required",
           "invoice_title": "English name, required",
           "invoice_weight": "Single Heavy ",
           "item_id ":"",
           "item_transactionid ":"",
           "sku ":" Chinese product name",
           "sku_code ":"distribution information",
           "hs_code ":"customs code"
       }
   ],
   "order_customerinvoicecode" : "Original order number, required",
   "product_id": "Transport mode ID, required ",
   "weight": "Total weight, optional, if there is a single weight on sku, do not fill in this item ",
   "product_imagepath": "Picture address, multiple picture addresses are separated by semicolons Open ",
   "shipper_name": "Sender's name, optional ",
   "shipper_companyname": "Sender's company name, optional ",
   "shipper_address1": "Sender's address 1, optional ",
"shipper_address2": "Sender address 2, optional",
   "shipper_address3": "Sender address 3, optional",
   "shipper_city": "Sender city, optional",
   "shipper_state": "Send Shipper State, optional ",
   "shipper_postcode":"Sender Postcode, optional ",
   "shipper_country":"Sender Country, optional ",
   "shipper_telephone":" Sender's Phone, optional ",
   "order_transactionurl": "Product sales address",
   "consignee_email": "Mailbox, optional",
   "consignee_companyname": "Recipient company name, if best, fill in",
   "order_codamount": "Account Collection",
   "order_codcurrency": "Currency, Standard Three-character Code",
   "order_cargoamount": "Actual Order Amount, Used for Customs Declaration",
   "order_insurance": "Insurance Amount",
   "consignee_taxno":"tax number ",
   "consignee_doorno":"door number "
}

### Response:

Parameter Name	| Data Type	|   REQUIRED	| Description
ack	| string	| Require	| true/false
message	| string	| Require	| The description for the messagePlease use URL to decode
order_transfercode	| string	|		
reference_number	| string	|		
tracking_number	| string	|		Tracking number
order_id	| string	|	Require	| Unique identification of orderQuery parameters of order and face order

Json:

{
   "ack":"true",
   "message":"%C7%EB%C7%F3%B3%C9%B9%A6",
   "order_transfercode": "",
   "reference_number":"",
   "tracking_number":"tracking number",
   "order_id":"xxxxxxx"
}

## getLabel

GET https://tms-label.czl.net/order/FastRpt/PDF_NEW.aspx?Format=lbl_sub一票多件161810499441.frx&PrintType=1&order_id=1024353

会跳转到类似这种文件: https://tms-label.czl.net/order/FastRpt/temp/12/19/b551a8f620d64ab8a0d24867fcc4e1a4.pdf

