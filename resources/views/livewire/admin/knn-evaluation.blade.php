<div class="p-4 sm:p-6 space-y-6">
    <!-- TICKER -->
    <div class="ticker-bar bg-purple-y2k text-espresso py-2">
        <div class="marquee inline-block text-xs font-extrabold tracking-widest uppercase">
            🧠 KNN ENGINE — K-NEAREST NEIGHBORS CLASSIFIER — EUCLIDEAN DISTANCE CALCULATOR — CONFUSION MATRIX EVALUATION — MIN-MAX NORMALIZATION — 🧠 KNN ENGINE —
        </div>
    </div>

    <!-- HEADER -->
    <div>
        <h1 class="text-3xl font-black text-espresso dark:text-cream uppercase tracking-tight">🧠 KNN Engine & Evaluasi</h1>
        <p class="text-coffee-600 dark:text-coffee-300 font-semibold mt-1">Pengujian nilai K dan visualisasi perhitungan jarak Euclidean secara detail.</p>
    </div>

    <!-- Top Grid: Konfigurasi & Confusion Matrix -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Input Parameter -->
        <div class="nb-card bg-cream dark:bg-coffee-900 p-6 space-y-4">
            <h2 class="text-lg font-black text-espresso dark:text-cream uppercase">1️⃣ Parameter Pengujian</h2>

            <!-- Select K -->
            <div>
                <label class="block text-xs font-black text-coffee-600 uppercase mb-1.5 tracking-wider">Nilai K (Jumlah Tetangga)</label>
                <select wire:model.change="k" class="nb-select w-full bg-white dark:bg-coffee-800 px-3 py-2.5 text-sm text-espresso dark:text-cream">
                    <option value="1">K = 1</option>
                    <option value="3">K = 3 (Standar)</option>
                    <option value="5">K = 5</option>
                    <option value="7">K = 7</option>
                    <option value="9">K = 9</option>
                </select>
                <p class="text-[10px] text-coffee-500 mt-1 font-bold">💡 Gunakan bilangan ganjil untuk menghindari voting seri.</p>
            </div>

            <!-- Select Member -->
            <div>
                <label class="block text-xs font-black text-coffee-600 uppercase mb-1.5 tracking-wider">Pilih Member (Uji Kasus)</label>
                <select wire:model.change="testUserId" class="nb-select w-full bg-white dark:bg-coffee-800 px-3 py-2.5 text-sm text-espresso dark:text-cream">
                    @foreach($allMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->behavior_label ?? 'Belum Labeled' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Hasil Klasifikasi -->
            <div class="retro-divider"></div>
            <div class="space-y-3">
                <h3 class="text-sm font-black text-espresso dark:text-cream uppercase">Hasil Prediksi ☕</h3>

                <div class="nb-card-sm bg-yellow-y2k/20 p-4 space-y-2">
                    <div class="flex justify-between text-xs font-bold text-coffee-700 dark:text-coffee-200">
                        <span>Label Asli:</span>
                        <span class="nb-badge bg-blue-y2k text-espresso">{{ $actualLabel }}</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-coffee-700 dark:text-coffee-200">
                        <span>Prediksi KNN:</span>
                        <span class="nb-badge @if($predictedLabel === $actualLabel) bg-matcha text-espresso @else bg-berry text-white @endif">{{ $predictedLabel }}</span>
                    </div>
                    @if($predictedLabel === $actualLabel)
                        <p class="text-center text-sm font-black text-matcha mt-1">✅ PREDIKSI BENAR!</p>
                    @else
                        <p class="text-center text-sm font-black text-berry mt-1">❌ PREDIKSI SALAH</p>
                    @endif
                </div>

                <div class="nb-card-sm bg-white dark:bg-coffee-800 p-3 text-[11px] space-y-1.5">
                    <p class="text-coffee-500 font-black mb-1 uppercase tracking-wider text-[9px]">Fitur & Normalisasi:</p>
                    <div class="flex justify-between text-coffee-600 dark:text-coffee-300 font-bold">
                        <span>Sweetness (1-5):</span>
                        <span>{{ $userFeatures[0] ?? 0 }} → <strong class="text-espresso dark:text-cream">{{ isset($normalizedTestFeatures[0]) ? round($normalizedTestFeatures[0], 2) : 0 }}</strong></span>
                    </div>
                    <div class="flex justify-between text-coffee-600 dark:text-coffee-300 font-bold">
                        <span>Coffee Ratio (0-1):</span>
                        <span>{{ $userFeatures[1] ?? 0 }} → <strong class="text-espresso dark:text-cream">{{ isset($normalizedTestFeatures[1]) ? round($normalizedTestFeatures[1], 2) : 0 }}</strong></span>
                    </div>
                    <div class="flex justify-between text-coffee-600 dark:text-coffee-300 font-bold">
                        <span>Avg Spending:</span>
                        <span>Rp {{ number_format($userFeatures[2] ?? 0, 0, ',', '.') }} → <strong class="text-espresso dark:text-cream">{{ isset($normalizedTestFeatures[2]) ? round($normalizedTestFeatures[2], 2) : 0 }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confusion Matrix -->
        <div class="lg:col-span-2 nb-card bg-cream dark:bg-coffee-900 p-6 space-y-5">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                <div>
                    <h2 class="text-lg font-black text-espresso dark:text-cream uppercase">2️⃣ Confusion Matrix</h2>
                    <p class="text-xs text-coffee-600 dark:text-coffee-300 mt-0.5 font-semibold">Validasi LOOCV (Leave-One-Out Cross Validation).</p>
                </div>
                <div class="text-right">
                    <div class="nb-card-sm inline-block bg-matcha px-4 py-2">
                        <span class="text-3xl font-black text-espresso">{{ $accuracy }}%</span>
                        <p class="text-[10px] font-black text-espresso uppercase">Akurasi ({{ $correctCount }}/{{ $totalEvalCount }})</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto nb-table bg-white dark:bg-coffee-800">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-espresso text-cream font-black">
                            <th class="px-4 py-3 border-r-2 border-b-3 border-black">Aktual \ Prediksi</th>
                            <th class="px-4 py-3 border-r-2 border-b-3 border-black text-center">☕ Kopi Strong</th>
                            <th class="px-4 py-3 border-r-2 border-b-3 border-black text-center">🍵 Minuman Manis</th>
                            <th class="px-4 py-3 border-b-3 border-black text-center">💎 Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $classes = [
                                'Pecinta Kopi Strong & Hemat' => '☕ Kopi Strong',
                                'Pecinta Minuman Manis/Kekinian' => '🍵 Minuman Manis',
                                'Pelanggan Premium (Suka Es Krim/Kue Mahal)' => '💎 Premium'
                            ];
                        @endphp
                        @foreach($classes as $actualKey => $actualLabelName)
                            <tr>
                                <td class="px-4 py-3 border-r-2 border-black font-black text-espresso dark:text-cream bg-coffee-100 dark:bg-coffee-800">{{ $actualLabelName }}</td>
                                @foreach($classes as $predKey => $predLabelName)
                                    @php
                                        $count = $confusionMatrix[$actualKey][$predKey] ?? 0;
                                        $isDiagonal = $actualKey === $predKey;
                                    @endphp
                                    <td class="px-4 py-3 border-r border-black/20 text-center font-black text-lg
                                        @if($isDiagonal && $count > 0) bg-matcha/30 text-espresso @elseif(!$isDiagonal && $count > 0) bg-berry/20 text-berry @else text-coffee-400 @endif">
                                        {{ $count }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nb-card-sm bg-yellow-y2k/15 p-3 text-[11px] text-coffee-600 dark:text-coffee-300 flex items-start gap-2.5 font-semibold">
                <span class="text-lg">💡</span>
                <p>Diagonal hijau = prediksi benar. Merah = salah klasifikasi. Semakin tinggi akurasi, semakin andal model KNN untuk sistem rekomendasi personal.</p>
            </div>
        </div>
    </div>

    <!-- 🔬 Detail Perhitungan Matematika KNN (Academic Tool) -->
    @if(!empty($scalingMins) && !empty($scalingMaxs) && count($knnDistanceDetails) > 0)
        @php
            $firstNeighbor = $knnDistanceDetails[0];
            $s_test = $normalizedTestFeatures[0] ?? 0;
            $c_test = $normalizedTestFeatures[1] ?? 0;
            $p_test = $normalizedTestFeatures[2] ?? 0;
            
            $s_train = $firstNeighbor['normalized_features'][0] ?? 0;
            $c_train = $firstNeighbor['normalized_features'][1] ?? 0;
            $p_train = $firstNeighbor['normalized_features'][2] ?? 0;
            
            $diff_s = $s_test - $s_train;
            $diff_c = $c_test - $c_train;
            $diff_p = $p_test - $p_train;
            
            $sq_s = pow($diff_s, 2);
            $sq_c = pow($diff_c, 2);
            $sq_p = pow($diff_p, 2);
            $sum_sq = $sq_s + $sq_c + $sq_p;
            $dist_calc = sqrt($sum_sq);
        @endphp
        <div class="nb-card bg-cream dark:bg-coffee-900 p-6 space-y-4">
            <div>
                <h2 class="text-lg font-black text-espresso dark:text-cream uppercase">🔬 Kalkulator Detail Matematika KNN</h2>
                <p class="text-xs text-coffee-600 dark:text-coffee-300 mt-0.5 font-semibold">
                    Simulasi perhitungan langkah demi langkah (Step-by-Step) dari Algoritma KNN.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Normalisasi Fitur -->
                <div class="nb-card-sm bg-white dark:bg-coffee-800 p-4 space-y-3">
                    <h3 class="text-xs font-black uppercase text-purple-y2k tracking-wider">
                        1. Min-Max Normalisasi Fitur (Skala 0.0 - 1.0)
                    </h3>
                    <div class="text-[11px] font-bold text-coffee-700 dark:text-coffee-300 space-y-2">
                        <p class="font-mono text-[9px] text-coffee-500 uppercase">Rumus: x_norm = (x - x_min) / (x_max - x_min)</p>
                        <div class="retro-divider !opacity-10 my-1"></div>
                        <div>
                            <span class="block text-espresso dark:text-cream font-black">Rasa Manis (Sweetness):</span>
                            <span>({{ $userFeatures[0] }} - {{ $scalingMins[0] }}) / ({{ $scalingMaxs[0] }} - {{ $scalingMins[0] }}) = <strong>{{ round($s_test, 4) }}</strong></span>
                        </div>
                        <div>
                            <span class="block text-espresso dark:text-cream font-black">Rasio Kopi (Coffee Ratio):</span>
                            <span>({{ $userFeatures[1] }} - {{ $scalingMins[1] }}) / ({{ $scalingMaxs[1] }} - {{ $scalingMins[1] }}) = <strong>{{ round($c_test, 4) }}</strong></span>
                        </div>
                        <div>
                            <span class="block text-espresso dark:text-cream font-black">Rata-rata Pengeluaran (Avg Spending):</span>
                            <span>({{ number_format($userFeatures[2]) }} - {{ number_format($scalingMins[2]) }}) / ({{ number_format($scalingMaxs[2]) }} - {{ number_format($scalingMins[2]) }}) = <strong>{{ round($p_test, 4) }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Jarak Euclidean -->
                <div class="nb-card-sm bg-white dark:bg-coffee-800 p-4 space-y-3">
                    <h3 class="text-xs font-black uppercase text-purple-y2k tracking-wider">
                        2. Jarak Euclidean (Contoh Terhadap Tetangga Terdekat: {{ $firstNeighbor['name'] }})
                    </h3>
                    <div class="text-[11px] font-bold text-coffee-700 dark:text-coffee-300 space-y-2">
                        <p class="font-mono text-[9px] text-coffee-500 uppercase">Rumus: d = √[ Σ (x_test - x_train)² ]</p>
                        <div class="retro-divider !opacity-10 my-1"></div>
                        <p>d = √[ ({{ round($s_test, 2) }} - {{ round($s_train, 2) }})² + ({{ round($c_test, 2) }} - {{ round($c_train, 2) }})² + ({{ round($p_test, 2) }} - {{ round($p_train, 2) }})² ]</p>
                        <p>d = √[ ({{ round($diff_s, 2) }})² + ({{ round($diff_c, 2) }})² + ({{ round($diff_p, 2) }})² ]</p>
                        <p>d = √[ {{ round($sq_s, 4) }} + {{ round($sq_c, 4) }} + {{ round($sq_p, 4) }} ]</p>
                        <p>d = √[ {{ round($sum_sq, 4) }} ] = <strong class="text-espresso dark:text-cream text-xs">{{ round($dist_calc, 4) }}</strong></p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Detail Perhitungan Jarak -->
    <div class="nb-card bg-cream dark:bg-coffee-900 p-6 space-y-4">
        <div>
            <h2 class="text-lg font-black text-espresso dark:text-cream uppercase">3️⃣ Perhitungan Jarak Euclidean</h2>
            <p class="text-xs text-coffee-600 dark:text-coffee-300 mt-0.5 font-semibold">Jarak geometris ter-normalisasi. <span class="nb-badge bg-purple-y2k text-espresso">K={{ $k }} tetangga</span> disorot.</p>
        </div>

        <div class="overflow-x-auto nb-table bg-white dark:bg-coffee-800">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-espresso text-cream">
                    <tr>
                        <th class="px-3 py-3 text-center">#</th>
                        <th class="px-3 py-3">Nama</th>
                        <th class="px-3 py-3 text-center">Fitur Asli</th>
                        <th class="px-3 py-3 text-center">Normalized</th>
                        <th class="px-3 py-3 text-center">Jarak (d)</th>
                        <th class="px-3 py-3">Label</th>
                        <th class="px-3 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($knnDistanceDetails as $index => $detail)
                        @php $isNeighbor = $index < $k; @endphp
                        <tr class="@if($isNeighbor) bg-purple-y2k/15 font-black @else hover:bg-caramel/5 @endif transition">
                            <td class="px-3 py-2.5 text-center font-black text-espresso dark:text-cream">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-black @if($isNeighbor) text-purple-y2k @else text-espresso dark:text-cream @endif">{{ $detail['name'] }}</td>
                            <td class="px-3 py-2.5 text-center font-mono text-[10px] text-coffee-600 dark:text-coffee-300">
                                [{{ $detail['features'][0] }}, {{ $detail['features'][1] }}, Rp{{ number_format($detail['features'][2], 0, ',', '.') }}]
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono text-[10px] text-coffee-600 dark:text-coffee-300">
                                [{{ round($detail['normalized_features'][0], 2) }}, {{ round($detail['normalized_features'][1], 2) }}, {{ round($detail['normalized_features'][2], 2) }}]
                            </td>
                            <td class="px-3 py-2.5 text-center font-black font-mono text-sm @if($isNeighbor) text-purple-y2k @else text-coffee-500 @endif">{{ number_format($detail['distance'], 4) }}</td>
                            <td class="px-3 py-2.5 text-[10px] text-coffee-600 dark:text-coffee-300 font-semibold">{{ $detail['label'] }}</td>
                            <td class="px-3 py-2.5 text-right">
                                @if($isNeighbor)
                                    <span class="nb-badge bg-purple-y2k text-espresso">🗳️ VOTE</span>
                                @else
                                    <span class="text-coffee-400 text-[9px] font-bold uppercase">Terlalu jauh</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
