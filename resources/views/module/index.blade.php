
@foreach($moduleList as $list)
    @if($list->parent_id==0)
        <div>{{$list->module_name}}</div>
    @else
       <div>&nbsp;&nbsp;{{$list->module_name}}</div>
    @endif
@endforeach