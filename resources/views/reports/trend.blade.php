
@php
    /*
    |--------------------------------------------------------------------------
    | Seven-Day Chart Presentation
    |--------------------------------------------------------------------------
    | Uses the existing backend $trend array.
    | No additional queries or data manipulation.
    */

    $chartId = \Illuminate\Support\Str::slug($heading)
        . '-heading';

    $chartIcon = $heading === 'Attendance'
        ? 'bi-people'
        : 'bi-arrow-left-right';

    $chartDescription = $heading === 'Attendance'
        ? 'Daily recorded library visits'
        : 'Daily book checkouts and returns';

    /*
    |--------------------------------------------------------------------------
    | Chart Scale
    |--------------------------------------------------------------------------
    | Keep the scale at least 1 to avoid dividing by zero.
    */

    $scale = 1;

    foreach (array_keys($series) as $key) {
        $values = array_column($trend, $key);

        if (!empty($values)) {
            $scale = max(
                $scale,
                (int) max($values)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Total
    |--------------------------------------------------------------------------
    | Used to display the empty-state notice when
    | no activity was recorded.
    */

    $chartTotal = 0;

    foreach ($trend as $day) {
        foreach (array_keys($series) as $key) {
            $chartTotal += (int) ($day[$key] ?? 0);
        }
    }
@endphp

<article class="dashboard-chart-card"
         aria-labelledby="{{ $chartId }}">

    <!-- CHART HEADER -->

    <header class="dashboard-chart-header">

        <div class="dashboard-chart-heading">

            <span class="dashboard-chart-icon"
                  aria-hidden="true">

                <i class="bi {{ $chartIcon }}"></i>

            </span>

            <div>

                <h3 id="{{ $chartId }}">
                    {{ $heading }} Trends
                </h3>

                <p>
                    {{ $chartDescription }}
                </p>

            </div>

        </div>

        <span class="dashboard-chart-period">
            7 days
        </span>

    </header>


    <!-- CHART SUMMARY -->

    <div class="dashboard-chart-overview">

        <div>

            <span class="dashboard-chart-total-label">
                TOTAL ACTIVITY
            </span>

            <strong class="dashboard-chart-total">
                {{ number_format($chartTotal) }}
            </strong>

        </div>

        <span class="dashboard-chart-overview-icon"
              aria-hidden="true">

            <i class="bi bi-graph-up-arrow"></i>

        </span>

    </div>


    <!-- CHART LEGEND -->

    <div class="dashboard-chart-legend">

        @foreach($series as $key => $label)

            <span class="dashboard-legend-item">

                <span class="dashboard-legend-dot
                             dashboard-legend-{{ $key }}"
                      aria-hidden="true"></span>

                {{ $label }}

            </span>

        @endforeach

    </div>


    <!-- HORIZONTAL CHART -->

    <div class="dashboard-chart-body">

        @foreach($trend as $day)

            <div class="dashboard-chart-row">

                <time datetime="{{ $day['date'] }}"
                      class="dashboard-chart-date">

                    {{ $day['label'] }}

                </time>

                <div class="dashboard-chart-series">

                    @foreach($series as $key => $label)

                        @php
                            $value = (int) ($day[$key] ?? 0);

                            $width = $value > 0
                                ? ($value / $scale) * 100
                                : 0;
                        @endphp

                        <div class="dashboard-chart-measure">

                            <div class="dashboard-chart-track">

                                <!-- ACCESSIBLE DATA VALUE -->


                                    <meter
                                        class="dashboard-chart-meter"
                                        min="0"
                                        max="{{ $scale }}" value="{{ $value }}"
                                        aria-label="{{ $day['label'] }}: {{ $value }} {{ $label }}"
                                    ></meter>


                                <!-- VISUAL BAR -->

                                <div
                                    class="dashboard-chart-fill dashboard-fill-{{ $key }}"
                                    style="width: {{ $width }}%;"
                                    aria-hidden="true"
                                ></div>

                            </div>

                            <span class="dashboard-chart-value">
                                {{ number_format($value) }}
                            </span>

                        </div>

                    @endforeach

                </div>

            </div>

        @endforeach

    </div>


    <!-- EMPTY DATA NOTICE -->

    @if($chartTotal === 0)

        <p class="dashboard-chart-empty">

            <i class="bi bi-info-circle"
               aria-hidden="true"></i>

            No recorded activity during this period.

        </p>

    @endif


    <!-- CHART FOOTER -->

    <footer class="dashboard-chart-footer">

        <i class="bi bi-info-circle"
           aria-hidden="true"></i>

        <span>
            Counts reflect the library's Manila calendar dates.
        </span>

    </footer>

</article>
