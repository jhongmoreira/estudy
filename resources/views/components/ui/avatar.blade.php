@props(['user'])
<span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full font-display font-bold']) }}
      style="background: {{ $user->avatarColor() }}; color: {{ \App\Support\Palette::onColor($user->avatarColor()) }}">{{ $user->initials() }}</span>
