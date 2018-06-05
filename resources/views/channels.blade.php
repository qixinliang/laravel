<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

        <title>端口设置</title>

        <!-- Fonts -->
        <!--<link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
		-->
		<style>
			.add-user{
				width:300px;
				height:150px;
				margin:0 auto;
				border: 5px solid #ccc;
				background-color:#aaa;
				position:absolute;
				top:100px;
				right:200px;
			}
            label.short-label{
                width:55px;height:24px;display:block;float:left;
                line-height:22px;color:#616161;padding-left:18px;margin-right:5px;
            } 
			.add-user  input{
				width: 150px;
				margin: 0 auto;
				text-align: left;
			}
            .button1{
                text-align: center;
                width:100px;
                height: 25px;
                margin-top:5px;
                margin-left:28px;

            }
		</style>
    </head>
    <body>
		<div>
			<table border="5">
				<caption>端口列表</caption>
				<tr>
					<th width="6%">序号</th>
				    <th>站点信息</th>
					<th width="35%">账号信息</th>
					<th width="10%">操作</th>
				</tr>
				@foreach($channels as $c)
				<tr>
					<td>{{$c['id']}}</td>
					<td>{{$c['name']}}</td>
					<td>暂无账号</td>
					<td>
						<p>
							<a data-id="account-add" websiteid="{{$c['id']}}" href="javascript:void(0)" class="add" onclick="aClick(this)">添加账号</a>
							<a href= "{{$c['reg_url']}}" class="reg">注册</a>
						</p>
					</td>
				</tr>

				@endforeach
			</table>

			<div id="add-user"  class="add-user" style="display:none">
				<form method="POST" action="/test">
					{{ csrf_field() }}
					<input id="website" type = "hidden" name="websiteId" value="">
					<p>
						<label class="short-label">用户名</label>
						<input id="username" name="username" type="text" placeholder="用户名...">
					</p>
					<p>
						<label class="short-label">密码</label>
						<input id="password" name="password" type="password" placeholder="密码...">
					</p>
					<p>
						<span>
							<button type="button" class="button1" id="save" onclick="my_save()">保存</button>
						</span>
						<span>
							<button type="button" class="button1" id="close" onclick="my_close()">关闭</button>
						</span>
					</p>
				</form>
			</div>
		</div>


    </body>
</html>
<script type="text/javascript">
    function aClick (obj) {  
		//小弹窗展开
        var el = document.getElementById("add-user");
        el.style.display="block";
		console.log(obj);
	
		//获取a标签自定义属性值
		var ownattr= obj.attributes['websiteid'].nodeValue; 
		console.log(ownattr);

		//设置表单提交中hidden input的value
		var website_input = document.getElementById('website');
		console.log(website_input);
		website_input.value = ownattr;
	}

	function my_save(){
		var website = document.getElementById('website').value;
		var username = document.getElementById('username').value;
		var password = document.getElementById('password').value;
		console.log(website);
		console.log(username);
		console.log(password);
        var formElement = document.forms[0];
        console.log(formElement);
        formElement.submit();

        var el = document.getElementById("add-user");
        el.style.display="none";
	}
	function my_close(){
        var el = document.getElementById("add-user");
        el.style.display="none";
	}
</script>
