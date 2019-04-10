
<div class="f1">aaa
<form  action="{{url('test')}}" method="post">
    <input name="name" value="" />
    <input class="btn btn-danger" type="submit" value="submit">
</form>
</div>
{{time()}}
@isset($res)
    @foreach ($res as $user)
        @if($user->user_id==2)
            二
        @endif
        <p>此用户为 {{ $user->user_id }}</p>
    @endforeach
@endisset



{{url('user/profile')}}
{{route('profile')}}


