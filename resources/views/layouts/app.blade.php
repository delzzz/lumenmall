<html>
<head>
    <title>应用程序名称 - @yield('title','title1')</title>
</head>
<body>

@section('sidebar')
    这是主布局的侧边栏。
@show

@section('content')
    内容
@show

@section('footer')
    页脚
@show
</body>
</html>
