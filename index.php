<?php
header("Content-type: text/html; charset=UTF-8");
include_once('include/connect.php');
if (!empty($_POST['keyword'])) {
	$keyword = htmlspecialchars($_POST['keyword'],ENT_QUOTES, "UTF-8");
} else {
	$keyword = null;
}

$m_building_class=array(
	1	=> 'マンション',
	2	=> 'ハイツ',
	3	=> 'コーポ',
	4	=> 'アパート',
	5	=> '貸家',
	6	=> 'テラスハウス'
);

$m_structure=array(
	1	=> 'RC造',
	2	=> 'SRC造',
	3	=> 'S造',
	4	=> 'PC造',
	5	=> 'HPC造',
	6	=> 'ALC造',
	7	=> 'S-ALC造',
	8	=> 'S-ALS造',
	9	=> '軽量鉄骨造',
	10	=> '重量鉄骨造',
	11	=> '木造',
);
?>
<html>
<head>
	<meta charset="utf-8">
	<title>プランアナウンス検索</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<form method="POST">
	<p>検索する情報を入力して下さい。</p>
	<dl class="search1">
		<dt><input type="text" name="keyword" value="<?php echo $keyword; ?>" placeholder="検索キーワードを入力して下さい" /></dt>
		<dd><button><span></span></button><dd>
	</dl>
<?php
if (!empty($keyword)) {
	$sql="
	select
		distinct p.id
		, p.name
		, p.outline
		, p.building_id
		, p.floor_layout
		, p.campaign_id
		, p.open_date
		, p.close_date
		, b.official_name
		, b.longitude
		, b.latitude
		, b.department_code
		, b.zipcode
		, b.prefecture_id
		, b.city_id
		, b.town_name
		, b.completed_date
		, b.building_floor
		, b.building_type
		, b.building_structure
		, IFNULL(c.title,'') as c_title
		, concat(IFNULL(pre.name,''), IFNULL(cit.name,''), IFNULL(b.town_name,''), IFNULL(b.address,'')) as address
		, size.maxsize
		, mi.item_group_cd
		, mi.item_ryaku
		, b.parking_kbn
	from
		plans p
		left join campaigns c
			on p.campaign_id=c.id
		left join m_items mi
			on p.floor_layout=mi.item_cd
		, buildings b
		, prefectures pre
		, cities cit
		, (
			select
				p.id
				, p.name
				, MAX(r.size) as maxsize
			from
				plans p
				, plan_rooms pr
				, rooms r
				, buildings b
			where
				p.id=pr.plan_id
			and
				pr.room_id=r.id
			and
				r.building_id=b.id
			and
				(r.end_date IS NULL or r.end_date >= CURDATE())
			and
				p.open_date >= '2018-05-14'
			and
				(p.close_date IS NULL or p.close_date >= CURDATE())
			and
				pr.deleted_flag=0
			and
				p.deleted_flag=0
			and
				r.deleted_flag=0
			and
				b.deleted_flag=0
			and
				( p.name like '%".$keyword."%' or b.official_name like '%".$keyword."%' )
			group by
				p.id
			order by
				p.name
		) size
	where
		mi.item_group_cd='floor_layout'
	and
		p.id=size.id
	and
		p.building_id=b.id
	and
		p.open_date <= CURDATE()
	and
		b.prefecture_id=pre.id
	and
		b.city_id=cit.id
	and
		p.open_date >= '2018-05-14'
	and
		(p.close_date >= CURDATE() or p.close_date IS NULL)
	and
		((p.name LIKE '%".$keyword."%') or (b.official_name LIKE '%".$keyword."%'))
	order by
		p.name
	";
	$result = mysqli_query($conn, $sql);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		if (strpos($row['name'], '***') !== false) {
			$url = "https://sub.****.jp/plan/{$row['id']}?utm_medium=email&utm_source=PlanAnnounce";
		} else {
			$url = "https://****.jp/plan/{$row['id']}?utm_medium=email&utm_source=PlanAnnounce";
		}

		$sql2 = "
			select
				count(*) as cnt
			from
				building_equipments be
				left join buildings b
					on be.building_id=b.id
				left join m_items mi
					on be.equipment=mi.item_cd,
				plans p,
				plan_rooms pr,
				rooms r
			where
				pr.plan_id=p.id
			and
				pr.room_id=r.id
			and
				r.building_id=b.id
			and
				mi.item_group_cd='building'
			and
				mi.item_cd='25'
			and
				p.open_date <= CURDATE()
			and
				p.open_date >= '2018-05-14'
			and
				(p.close_date >= CURDATE() or p.close_date IS NULL)
			and
				p.deleted_flag=0
			and
				b.deleted_flag=0
			and
				mi.del_flg=0
			and
				be.deleted_flag=0
			and p.id=".$row['id'];
		$result2 = mysqli_query($conn, $sql2);
		$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);

		$sql3="
			select
				sum(r.twinset_flg) as twins
			from
				plans p
				left join plan_rooms pr
					on p.id=pr.plan_id
				left join rooms r
					on pr.room_id=r.id
			where
				(p.close_date >= CURDATE() or p.close_date IS NULL)
			and
				p.deleted_flag=0
			and
				pr.deleted_flag=0
			and
				r.deleted_flag=0
			and
				p.id=".$row['id'];
		$result3 = mysqli_query($conn, $sql3);
		$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);

		$sql4="
			select
				p.name, count(r.id) as rcnt
			from
				rooms r
				left join plan_rooms pr
					on r.id=pr.room_id
				left join plans p
					on pr.plan_id=p.id
			where
				r.deleted_flag=0
			and
				(r.end_date is null or r.end_date>=curdate())
			and
				p.deleted_flag=0
			and
				pr.deleted_flag=0
			and
				p.open_date>='2018-05-14'
			and
				p.id=".$row['id']."
			group by
				p.name
		";
		$result4 = mysqli_query($conn, $sql4);
		$row4 = mysqli_fetch_array($result4, MYSQLI_ASSOC);

		$outline=str_replace("<p", "\n<p", $row['outline']);
		$outline=str_replace("<br>", "\n", $outline);
		$outline=str_replace("<br />", "\n", $outline);
		$outline=strip_tags($outline);
		$str = "♠".$row['name']."\n";
		$str.="　".$url."\n";
		$str .= "　\n";
		if ($row['c_title']) {
			$str.="　**********************************************\n";
			$str.="　　".$row['c_title']."\n";
			$str.="　　キャンペーン対象期間: ".str_replace('-', '/', substr($row['open_date'], 0, 10))." ～ ".str_replace('-', '/', substr($row['close_date'], 0, 10))."\n";
			$str.="　**********************************************\n";
		}
		$str .="\n";
		echo "<div class='plan'><div><a href='".$url."' target='_blank' class='planname'>";
		if ($row['c_title']) {
			echo "<span class='red'>【キャンペーン】</span>";
		}
		echo $row['name']." (".$row['official_name'].")</a></div>";
		if ($row['c_title']) {
			echo "<div class='campaign_span'>";
			echo "　<span>".$row['c_title']."　( ".str_replace('-', '/', substr($row['open_date'], 0, 10))." ～ ".str_replace('-', '/', substr($row['close_date'], 0, 10))." )</span>";
			echo "</div>";
		}
			
		echo "<div>〒".$row['zipcode']." ".$row['address']."<input type='button' onclick=window.open('https://maps.google.co.jp/maps/search/".$row['address']."','_blank') value='GoogleMAP' class='googlemap'></div>";
		echo "<table>";
		echo "<tr><th>間取り</th><td>".$row['item_ryaku']."</td><td rowspan='8'><textarea id='target".$row['id']."' readonly>".$str."</textarea><br /><button class='btn' data-clipboard-action='copy' data-clipboard-target='#target".$row['id']."'>クリップボードにコピー</button></td></tr>";
		echo "<tr><th>専有面積</th><td>".$row['maxsize']."㎡</td></tr><th>築年月</th><td>".str_replace('-', '/', $row['completed_date'])."</td></tr><th>総階数</th><td>".$row['building_floor']."階建て</td></tr>";
		echo "<tr><th>建物種別</th><td>";
		if(!empty($m_building_class[$row['building_type']])){
			echo $m_building_class[$row['building_type']];
		}
		echo "</td></tr>";
		echo "<tr><th>建物構造</th><td>";
		if(!empty($m_structure[$row['building_structure']])){
			echo $m_structure[$row['building_structure']];
		}
		echo "</td></tr>";
		if ($row['floor_layout'] >= 41) {
			$min_users = 4;
		} elseif ($row['floor_layout'] >= 31) {
			$min_users = 3;
		} elseif ($row['floor_layout'] >= 21) {
			$min_users = 2;
		} else {
			$min_users = 1;
		}
		if ( $row2['cnt'] != 0 || $row3['twins'] < $row4['rcnt']){
			$max_users = $min_users;
		} else {
			$max_users = $min_users * 2;
		}
		echo "<tr><th>設定人数</th><td>".$min_users."人 (最大: ".$max_users."人 )</td></tr>";
		echo "<tr><th>駐車場案内</th>".$park_flg = ($row['parking_kbn']==0) ? '-' : '可'."<td>".$park_flg."</td></tr>";
		echo "</table>";
		echo "<img src='http://maps.googleapis.com/maps/api/staticmap?center=".$row['latitude'].",".$row['longitude']."&key=**********&zoom=15&format=png&sensor=false&size=520x270&maptype=roadmap&markers=".$row['latitude'].",".$row['longitude']."' />";
		echo "</div>";
	}
}
?>

<script src="https://cdn.jsdelivr.net/clipboard.js/1.5.3/clipboard.min.js"></script>
<script type="text/javascript">
var clipboard = new Clipboard('.btn');
clipboard.on('success', function(e) {
});
</script>
</form>
</body>
</html>
