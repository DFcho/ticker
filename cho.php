<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/header.php"; 
	$_SERVER["REMOTE_ADDR"] ;
?>
	<div class="img_one">
		<h2 class="sub2_top">상품 리스트</h2>
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
<?php 
	require_once $_SERVER["DOCUMENT_ROOT"]."/include/bottom.php"; 
?>
