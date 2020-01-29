<div class="container text-center mb-5"> 
  <!-- Navigation till Tab view -->
  <ul class="nav nav-pills nav-justified desktop">
    <!--<li> <a href="#"><span class="glyphicon glyphicon-chevron-left"></span></a></li>-->
    @foreach($additionalOptions as $key => $option)
    <li class="nav-item"><a 
      class="nav-link 
      @if($currentOption == $key) 
      active
      @endif
      " 
      @if($option['status'] == true)
          href="{{$option['url']}}"
      @endif
    >{{$option['title']}}</a></li>
    @endforeach
    <!--<li><a href="#"><span class="glyphicon glyphicon-chevron-right"></span></a></li>-->
  </ul>
  <!-- Navigation till Tab view -->

  <!-- Navigation for mobile view -->
  <ul class="nav nav-pills below-desktop d-none">
    @if($previous)
    <li class="nav-item"> <a class="nav-link"  href="{{$additionalOptions[$previous]['url']}}"><span class="glyphicon glyphicon-chevron-left"></span></a></li>
    @endif
    <li class="nav-item">
      <button class="btn btn-default dropdown-toggle" type="button" id="menu1" data-toggle="dropdown">
      {{$additionalOptions[$currentOption]['title']}}
      <span class="caret"></span></button>
      <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
        @foreach($additionalOptions as $key => $option)
        @if($option['status'] == true && $key != $currentOption)
          <li role="presentation"><a role="menuitem" tabindex="-1" href="{{$option['url']}}">{{$option['title']}}</a></li>
        @endif
        @endforeach
      </ul>
    </li>
    @if($next)
    <li class="nav-item"><a class="nav-link"  href="{{$additionalOptions[$next]['url']}}"><span class="glyphicon glyphicon-chevron-right"></span></a></li>
    @endif
  </ul>
  <!-- Navigation for mobile view -->
</div>
