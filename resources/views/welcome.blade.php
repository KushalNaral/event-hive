<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Statistics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.3/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div x-data="dashboard()" class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Event Statistics Dashboard</h1>

        <div class="mb-8">
            <label for="userSelect" class="block mb-2 font-semibold">Select User:</label>
            <select id="userSelect" x-model="selectedUserId" @change="changeUser()" class="w-full md:w-1/2 p-2 border rounded">
                <option value="">Select a user</option>
                <template x-for="user in users" :key="user.id">
                    <option :value="user.id" x-text="user.name + ' (ID: ' + user.id + ')'"></option>
                </template>
            </select>
        </div>

        <div x-show="selectedUserId" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4">Overview</h2>
                    <p>User: <span x-text="selectedUser.name"></span></p>
                    <p>Total Events: <span x-text="totalEvents"></span></p>
                    <p>Average Score: <span x-text="averageScore.toFixed(4)"></span></p>
                    <p>Highest Score: <span x-text="highestScore.toFixed(4)"></span></p>
                    <p>Lowest Score: <span x-text="lowestScore.toFixed(4)"></span></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4">Score Distribution</h2>
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4">Top 5 Events</h2>
                    <ul>
                        <template x-for="event in topEvents" :key="event.id">
                            <li>
                                Event ID: <span x-text="event.id"></span>,
                                Score: <span x-text="event.score.toFixed(4)"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-xl font-semibold mb-4">Event Comparison</h2>
                <div class="flex flex-wrap gap-4 mb-4">
                    <template x-for="event in events" :key="event.id">
                        <button
                            @click="toggleEventSelection(event.id)"
                            :class="{'bg-blue-500 text-white': selectedEvents.includes(event.id), 'bg-gray-200': !selectedEvents.includes(event.id)}"
                            class="px-3 py-1 rounded"
                            x-text="'Event ' + event.id"
                        ></button>
                    </template>
                </div>
                <canvas id="eventComparisonChart"></canvas>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Individual Event Details</h2>
                <div class="mb-4">
                    <label for="eventSelect" class="block mb-2">Select Event:</label>
                    <select id="eventSelect" x-model="selectedEventId" @change="updateEventDetails()" class="w-full p-2 border rounded">
                        <option value="">Select an event</option>
                        <template x-for="event in events" :key="event.id">
                            <option :value="event.id" x-text="'Event ' + event.id"></option>
                        </template>
                    </select>
                </div>
                <div x-show="selectedEventId" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Event Details</h3>
                        <p>Event ID: <span x-text="selectedEventId"></span></p>
                        <p>Final Score: <span x-text="selectedEvent?.score.toFixed(4)"></span></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Preference Scores</h3>
                        <canvas id="eventPreferencesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboard() {
            return {
                users: @json($users),
                selectedUserId: '',
                selectedUser: null,
                events: [],
                selectedEvents: [],
                selectedEventId: '',
                selectedEvent: null,
                totalEvents: 0,
                averageScore: 0,
                highestScore: 0,
                lowestScore: 1,
                topEvents: [],
                scoreDistributionChart: null,
                eventComparisonChart: null,
                eventPreferencesChart: null,

                init() {
                    // Initialize with empty state
                },

                changeUser() {
                    if (this.selectedUserId) {
                        this.selectedUser = this.users.find(user => user.id == this.selectedUserId);
                        this.events = this.selectedUser.events;
                        this.updateDashboard();
                    } else {
                        this.resetDashboard();
                    }
                },

                updateDashboard() {
                    this.totalEvents = this.events.length;
                    this.averageScore = this.events.reduce((sum, event) => sum + event.score, 0) / this.totalEvents;
                    this.highestScore = Math.max(...this.events.map(event => event.score));
                    this.lowestScore = Math.min(...this.events.map(event => event.score));
                    this.topEvents = this.events.sort((a, b) => b.score - a.score).slice(0, 5);

                    this.initScoreDistributionChart();
                    this.initEventComparisonChart();

                    this.selectedEvents = [];
                    this.selectedEventId = '';
                    this.selectedEvent = null;
                },

                resetDashboard() {
                    this.selectedUser = null;
                    this.events = [];
                    this.totalEvents = 0;
                    this.averageScore = 0;
                    this.highestScore = 0;
                    this.lowestScore = 1;
                    this.topEvents = [];
                    this.selectedEvents = [];
                    this.selectedEventId = '';
                    this.selectedEvent = null;

                    if (this.scoreDistributionChart) {
                        this.scoreDistributionChart.destroy();
                    }
                    if (this.eventComparisonChart) {
                        this.eventComparisonChart.destroy();
                    }
                    if (this.eventPreferencesChart) {
                        this.eventPreferencesChart.destroy();
                    }
                },

                updateEventDetails() {
                    this.selectedEvent = this.events.find(event => event.id == this.selectedEventId);
                    if (this.selectedEvent) {
                        this.initEventPreferencesChart();
                    }
                },

                toggleEventSelection(eventId) {
                    const index = this.selectedEvents.indexOf(eventId);
                    this.updateEventComparisonChart();
                },

                initScoreDistributionChart() {
                    const ctx = document.getElementById('scoreDistributionChart').getContext('2d');
                    if (this.scoreDistributionChart) {
                        this.scoreDistributionChart.destroy();
                    }
                    this.scoreDistributionChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['0-0.2', '0.2-0.4', '0.4-0.6', '0.6-0.8', '0.8-1'],
                            datasets: [{
                                label: 'Number of Events',
                                data: this.calculateScoreDistribution(),
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Events'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Score Range'
                                    }
                                }
                            }
                        }
                    });
                },

                calculateScoreDistribution() {
                    const distribution = [0, 0, 0, 0, 0];
                    this.events.forEach(event => {
                        const index = Math.min(Math.floor(event.score * 5), 4);
                        distribution[index]++;
                    });
                    return distribution;
                },

                initEventComparisonChart() {
                    const ctx = document.getElementById('eventComparisonChart').getContext('2d');
                    if (this.eventComparisonChart) {
                        this.eventComparisonChart.destroy();
                    }
                    this.eventComparisonChart = new Chart(ctx, {
                        type: 'radar',
                        data: {
                            labels: Object.keys(this.events[0].preferences),
                            datasets: []
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
                },

                updateEventComparisonChart() {
                    const datasets = this.selectedEvents.map(eventId => {
                        const event = this.events.find(e => e.id == eventId);
                        return {
                            label: `Event ${event.id}`,
                            data: Object.values(event.preferences),
                            fill: true,
                            backgroundColor: `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.2)`,
                            borderColor: `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 1)`,
                            pointBackgroundColor: `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 1)`,
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 1)`
                        };
                    });

                    console.log("dataseets", datasets);
                    console.log("selectedEvents", this.selectedEvents);
                    this.eventComparisonChart.data.datasets = datasets;
                    this.eventComparisonChart.update();
                },

                initEventPreferencesChart() {
                    const ctx = document.getElementById('eventPreferencesChart').getContext('2d');
                    if (this.eventPreferencesChart) {
                        this.eventPreferencesChart.destroy();
                    }
                    this.eventPreferencesChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(this.selectedEvent.preferences),
                            datasets: [{
                                label: 'Preference Scores',
                                data: Object.values(this.selectedEvent.preferences),
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Score'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>
