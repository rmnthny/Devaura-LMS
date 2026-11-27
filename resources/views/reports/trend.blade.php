@php
    $chartId = Str::slug($heading).'-heading';
    $scale = max(1, ...array_map(fn ($key) => max(array_column($trend, $key)), array_keys($series)));
@endphp
<div class="col">
    <section class="card card-body h-100 p-3" aria-labelledby="{{ $chartId }}">
        <h3 class="h6" id="{{ $chartId }}">{{ $heading }}</h3>
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    @foreach($series as $label)
                        <th scope="col">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($trend as $day)
                    <tr>
                        <th scope="row"><time datetime="{{ $day['date'] }}">{{ $day['label'] }}</time></th>
                        @foreach($series as $key => $label)
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <meter class="w-100" aria-hidden="true" min="0" max="{{ $scale }}" value="{{ $day[$key] }}"></meter>
                                    <span>{{ number_format($day[$key]) }}</span>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>
