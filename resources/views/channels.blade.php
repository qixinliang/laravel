<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>端口设置</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    </head>
    <body>
		<div>
			<table border="5">
				<caption>端口列表</caption>
				<tr>
					<th width="6%">序号</th>
				    <th>站点信息</th>
					<th width="40%">账号信息</th>
					<th width="10%">操作</th>
				</tr>
				@foreach($channels as $c)
				<tr>
					<td>{{$c['id']}}</td>
					<td>{{$c['name']}}</td>
					<td>暂无账号</td>
					<td>
						<p>
							<a href="javascript:void(0)" class="add" onclick="aClick()">添加账号</a>
							<a href= "{{$c['reg_url']}}" class="reg">去注册</a>
						</p>
					</td>
				</tr>


				@endforeach
			</table>

				<div id="add-user" >
					<form>
						<!--<input type = "hidden" name="websiteId" value="{{$c['id']}}">-->
						<p>
							<label>用户名</label>
							<input id="username" name="username" type="text" placeholder="用户名...">
							
						</p>
						<p>
							<label>密码</label>
							<input id="password" name="password" type="password" placeholder="密码...">
						</p>
					</form>
				</div>
		</div>


    </body>
</html>
<script type="text/javascript">
    function aClick () {  
		//window.location = "http://www.baidu.com";  
	} 
</script>
