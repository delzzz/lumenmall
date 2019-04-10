<html>
<head></head>
<body>
<script src="{{  Url::asset('/js/jquery-1.7.1.min.js') }}"></script>
<script>
    $.ajax({
        url: "http://cishoo.test.qyuedai.com/specList",
        headers:{
            'token':'TW96aWxsYS81LjAgKFdpbmRvd3MgTlQgNi4xOyBXT1c2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzU2LjAuMjkyNC44NyBTYWZhcmkvNTM3LjM2fHsidXNlcl9pZCI6MSwidXNlcl9uYW1lIjoiY2NjIn18Y2lzaG9vMTIzNDU2'
        },
        success: function (res) {
            console.log(res);
        },

    });
</script>
</body>
</html>
{{--@component('inc.alert')--}}
    {{--@slot('title')--}}
       {{--neeeee--}}
    {{--@endslot--}}
    {{--你没有权限访问这个资源！--}}
{{--@endcomponent--}}

