<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/header.php"; 
	$_SERVER["REMOTE_ADDR"] ;
?>
	
	<div class="img_one">
		<h2 class="sub2_top">장바구니</h2>
		<div class="img_div" onclick="location.href='/ticker/top_wear.php?conv=top'">
			<img width="200" height="200" src="/img/top_wear.jpg">
			<div class="wh_font">티커 상의</div>
			<div class="wh_font">가격 : 30,000원</div>
		</div>
		<div class="img_div" onclick="location.href='/ticker/bottom_wear.php?conv=bottom'">
			<img width="200" height="200" src="/img/bottom_wear.jpg">
			<div class="wh_font">티커 하의</div>
			<div class="wh_font">가격 : 40,000원</div>
		</div>
		<div class="img_div" onclick="location.href='/ticker/shoes.php?conv=shoes'">
			<img width="200" height="200" src="/img/shoes.jpg">
			<div class="wh_font">티커 신발</div>
			<div class="wh_font">가격 : 80,000원</div>
		</div>
		<div class="img_div" onclick="location.href='/ticker/outer.php?conv=outer'">
			<img width="200" height="200" src="/img/outer.jpg">
			<div class="wh_font">티커 아우터</div>
			<div class="wh_font">가격 : 100,000원</div>
		</div>
	</div>
	<div>
		<button onclick="location.href='/ticker/buy.php'" class = "by_btn basket_buy">구매하기</button>
	</div>
	<!-- Mirae Talk Script Ver 2.0   -->
	<script type="text/javascript">
		var mi_conv = { 
			type : 'cart'		// 장바구니 구분값 cart
			, price : "250000"		// 장바구니 담은 상품 전체 합계 금액
			, quantity : "1"		// 장바구니 담은 상품 수
			,ticker_id : "rjvck_5f5e911e89bf734444444"
			,adkey : "rjvckwwwwwww"
			, product : [
						 {
							no			: "1234-a" ,	// 상품 고유 번호
							name		: "티커 상의" ,	// 상품명
							price		: "30000" ,	// 상품금액
							link		: "https://nhlyvly.com" ,	// 상품 url 
							img_link	: "https://nhlyvly.com/web/product/medium/202106/58371c6dba662b97a8dd7a40cec6a04c.jpg"	// 상품 대표 이미지 url 
						},
						{
							no			: "5547-a" ,	// 상품 고유 번호
							name		: "티커 하의" ,	// 상품명
							price		: "40000" ,	// 상품금액
							link		: "https://nhlyvly.com" ,	// 상품 url 
							img_link	: "https://nhlyvly.com/web/product/medium/202106/58371c6dba662b97a8dd7a40cec6a04c.jpg"	// 상품 대표 이미지 url 
						},
						{
							no			: "69695422121a" ,	// 상품 고유 번호
							name		: "티커 아우터" ,	// 상품명
							price		: "100000" ,	// 상품금액
							link		: "https://nhlyvly.com" ,	// 상품 url 
							img_link	: "https://nhlyvly.com/web/product/medium/202106/58371c6dba662b97a8dd7a40cec6a04c.jpg"	// 상품 대표 이미지 url 
						},
						{
							no			: "87454152zwea" ,	// 상품 고유 번호
							name		: "티커 신발" ,	// 상품명
							price		: "80000" ,	// 상품금액
							link		: "https://nhlyvly.com" ,	// 상품 url 
							img_link	: "https://nhlyvly.com/web/product/medium/202106/58371c6dba662b97a8dd7a40cec6a04c.jpg"	// 상품 대표 이미지 url 
						}
						// 장바구니 담긴 상품 갯수 만큼 해당 영역 loop
					]	
		};
	</script>
<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/bottom.php"; 
?>
