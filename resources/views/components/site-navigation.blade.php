<nav class="bg-sky-900 text-sky-50">

    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <ul class="flex space-x-4 p-4">
            @foreach($menu_items as $item)
                <li><a href="{{$item['url']}}">{{$item['name']}}</a></li>
            @endforeach
        </ul>
    </div>

</nav>
