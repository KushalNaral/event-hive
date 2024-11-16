<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recommendation Analytics Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <style>
        .score-card {
            transition: all 0.3s ease;
        }
        .score-card:hover {
            transform: translateY(-5px);
        }
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        </style>
    </head>
    <body class="bg-gray-50">
        <div class="loading" id="loadingOverlay">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Recommendation Analytics Dashboard</h1>
                <div class="text-sm text-gray-500">Last updated: {{ now()->format('M d, Y H:i') }}</div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <input type="text" id="dateRange" name="date_range" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Select date range">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="started">Started</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Score Threshold</label>
                        <input type="number" name="score_threshold" step="0.1" min="0" max="1" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g. 0.7">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>


            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="score-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-sm font-medium text-gray-500 mb-2">Total Recommendations</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalRecommendations }}</div>
                    <div class="mt-2 text-sm {{ $recommendationTrend > 0 ? 'text-green-500' : 'text-red-500' }}">
                        {{ abs($recommendationTrend) }}% {{ $recommendationTrend > 0 ? '↑' : '↓' }} from last period
                    </div>
                </div>

                <div class="score-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-sm font-medium text-gray-500 mb-2">Average Final Score</div>
                    <div class="text-3xl font-bold text-gray-800">{{ number_format($averageFinalScore, 2) }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-500 rounded-full h-2" style="width: {{ $averageFinalScore * 100 }}%"></div>
                    </div>
                </div>

                <div class="score-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-sm font-medium text-gray-500 mb-2">Success Rate</div>
                    <div class="text-3xl font-bold text-gray-800">{{ number_format($successRate, 1) }}%</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-green-500 rounded-full h-2" style="width: {{ $successRate }}%"></div>
                    </div>
                </div>

                <div class="score-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-sm font-medium text-gray-500 mb-2">Average Processing Time</div>
                    <div class="text-3xl font-bold text-gray-800">{{ number_format($avgProcessingTime, 1) }}s</div>
                    <div class="mt-2 text-sm {{ $processingTimeTrend < 0 ? 'text-green-500' : 'text-red-500' }}">
                        {{ abs($processingTimeTrend) }}% {{ $processingTimeTrend < 0 ? '↓' : '↑' }} from last period
                </div>
            </div>
        </div>

        <!-- Score Distribution Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Score Components Distribution</h2>
                <canvas id="scoreDistributionChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Preference Match Analysis</h2>
                <canvas id="preferenceMatchChart"></canvas>
            </div>
        </div>

        <!-- Detailed Metrics -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Detailed Score Breakdown</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach([
                'interaction' => ['Interaction Score', 'blue'],
                'preference' => ['Preference Score', 'green'],
                'popularity' => ['Popularity Score', 'purple'],
                'capacity' => ['Capacity Score', 'yellow'],
                'rating' => ['Rating Score', 'red'],
                'user_correlation' => ['User Correlation', 'indigo']
                ] as $key => $meta)
                <div class="score-card">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $meta[0] }}</h3>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($scoreBreakdown[$key] ?? 0, 2) }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-{{ $meta[1] }}-500 rounded-full h-2"
                            style="width: {{ ($scoreBreakdown[$key] ?? 0) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
    // Initialize date range picker
    flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        maxDate: "today",
        defaultDate: [
            new Date().setDate(new Date().getDate() - 30),
            new Date()
        ]
    });

    // Initialize Charts
    const scoreCtx = document.getElementById('scoreDistributionChart').getContext('2d');
    new Chart(scoreCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($scoreDistribution)) !!},
        datasets: [{
            label: 'Average Score',
            data: {!! json_encode(array_values($scoreDistribution)) !!},
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1
                }]
    },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1
                }
            }
        }
    });

    const preferenceCtx = document.getElementById('preferenceMatchChart').getContext('2d');
    new Chart(preferenceCtx, {
        type: 'radar',
        data: {
            labels: {!! json_encode(array_keys($preferenceDistribution)) !!},
        datasets: [{
            label: 'Match Percentage',
            data: {!! json_encode(array_values($preferenceDistribution)) !!},
            backgroundColor: 'rgba(34, 197, 94, 0.2)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 2
                }]
    },
        options: {
            scales: {
                r: {
                    beginAtZero: true,
                    max: 1
                }
            }
        }
    });

    // Form submission handling
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById('loadingOverlay').style.display = 'flex';

        const formData = new FormData(this);
        const queryString = new URLSearchParams(formData).toString();

        fetch(`${window.location.pathname}?${queryString}`)
            .then(response => response.text())
            .then(html => {
                document.body.innerHTML = html;
                document.getElementById('loadingOverlay').style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingOverlay').style.display = 'none';
            });
    });
    </script>

    <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Top Performing Events</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Event Name
                        </th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Average Score
                        </th>
                        <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                            Recommendation Count
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach($topEvents as $event)
                    <tr>
                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                            <div class="text-sm leading-5 text-gray-900">{{ $event->title }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                            <div class="text-sm leading-5 font-medium text-gray-900">
                                {{ number_format($event->avg_score * 100, 1) }}%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                            <div class="text-sm leading-5 text-gray-900">{{ $event->recommendation_count }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        tippy(element, {
            content: element.getAttribute('data-tooltip'),
            placement: 'top'
        });
    });

    // Add real-time chart updates
    setInterval(() => {
        if (!document.hidden) {
            fetch(window.location.pathname + '/refresh-data')
                .then(response => response.json())
                .then(data => {
                    updateCharts(data);
                    updateMetrics(data);
                });
        }
    }, 30000); // Update every 30 seconds
});

function updateCharts(data) {
    // Update score distribution chart
    scoreChart.data.datasets[0].data = Object.values(data.scoreDistribution);
    scoreChart.update();

    // Update preference match chart
    preferenceChart.data.datasets[0].data = Object.values(data.preferenceDistribution);
    preferenceChart.update();
}

function updateMetrics(data) {
    // Update summary cards
    document.querySelector('#totalRecommendations').textContent = data.totalRecommendations;
    document.querySelector('#avgFinalScore').textContent = data.averageFinalScore.toFixed(2);
    document.querySelector('#successRate').textContent = data.successRate.toFixed(1) + '%';
    document.querySelector('#avgProcessingTime').textContent = data.avgProcessingTime.toFixed(1) + 's';
}
</script>

</body>
</html>
