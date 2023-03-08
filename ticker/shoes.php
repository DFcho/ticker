<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/header.php"; 
	$_SERVER["REMOTE_ADDR"] ;
?>
<?php 
	$conv = 	$_GET['conv'];
?>
	<div class="img_one">
		<div class="detail_con">
			<img width="400" height="400"  src="/img/shoes.jpg">
			<table>
				<colgroup>
					<col width="150" >
					<col width="150">
				</colgroup>
				<tbody class="detail_one">
					<tr>
						<td>상품명</td>
						<td>티커 신발</td>
					</tr>
					<tr>
						<td>가격</td>
						<td>80,000원</td>
					</tr>
					<tr>
						<td>원산지</td>
						<td>한국</td>
					</tr>
					<tr>
						<td>판매처</td>
						<td>Ticker</td>
					</tr>
					<tr>
						<td>관리자</td>
						<td>조용화</td>
					</tr>
					<tr>
						<td>
							<button onclick="location.href='/ticker/basket.php'" class = "by_btn">장바구니</button>
						</td>
						<td>
							<button onclick="location.href='/ticker/buy.php'" class = "by_btn">구매하기</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

<script type="text/javascript">
	var mi_conv = { 
		type : 	"view",		// 상품 조회 구분값 view
		ticker_id : "rjvck_5f5e911e89bf73333333",
		adkey : "rjvckrrrrrrr",
		product : {
			no			: "69695422121a" ,	// 상품 고유 번호
			name		: "티커 아우터" ,	// 상품명
			price		: "100000" ,	// 상품금액
			link		: "https://nhlyvly.com" ,	// 상품 url 
			img_link	: "https://nhlyvly.com/web/product/medium/202106/58371c6dba662b97a8dd7a40cec6a04c.jpg"	// 상품 대표 이미지 url 
		}
	};
</script>
<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/bottom.php"; 
?>
