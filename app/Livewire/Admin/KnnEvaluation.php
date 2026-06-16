<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\KNearestNeighborsService;
use Livewire\Component;

class KnnEvaluation extends Component
{
    public $k = 3;
    public $testUserId = null;
    
    // Evaluation results
    public $accuracy = 0;
    public $correctCount = 0;
    public $totalEvalCount = 0;
    public $confusionMatrix = [];

    // Selected user classification breakdown
    public $predictedLabel = '';
    public $actualLabel = '';
    public $userFeatures = [];
    public $normalizedTestFeatures = [];
    public $knnDistanceDetails = [];
    public $scalingMins = [];
    public $scalingMaxs = [];

    public function mount()
    {
        // Select first member as default test user
        $firstMember = User::where('role', 'member')->first();
        if ($firstMember) {
            $this->testUserId = $firstMember->id;
            $this->calculateTestUserKnn();
        }
        $this->runValidationMatrix();
    }

    public function updatedK()
    {
        $this->validate(['k' => 'required|integer|min:1|max:15']);
        $this->calculateTestUserKnn();
        $this->runValidationMatrix();
    }

    public function updatedTestUserId()
    {
        $this->calculateTestUserKnn();
    }

    /**
     * Run KNN for the specific selected test user and return step-by-step distance log
     */
    public function calculateTestUserKnn()
    {
        if (!$this->testUserId) return;

        $testUser = User::find($this->testUserId);
        if (!$testUser) return;

        $this->actualLabel = $testUser->behavior_label ?? 'Belum Terklasifikasi';
        $this->userFeatures = $testUser->getKnnFeatures();

        // Instantiate KNN service
        $knn = new KNearestNeighborsService($this->k);

        // Train using ALL OTHER members
        $trainingUsers = User::where('role', 'member')
            ->where('id', '!=', $testUser->id)
            ->whereNotNull('behavior_label')
            ->get();

        foreach ($trainingUsers as $user) {
            $knn->train($user->getKnnFeatures(), $user->behavior_label, $user->name);
        }

        if ($knn->getDatasetCount() > 0) {
            $result = $knn->classify($this->userFeatures);
            $this->predictedLabel = $result['label'];
            $this->knnDistanceDetails = $result['all_distances'];
            $this->normalizedTestFeatures = $result['normalized_test'];
            $this->scalingMins = $result['scaling_mins'];
            $this->scalingMaxs = $result['scaling_maxs'];
        }
    }

    /**
     * Run a Leave-One-Out validation on the training dataset to build the confusion matrix
     */
    public function runValidationMatrix()
    {
        $labeledUsers = User::where('role', 'member')
            ->whereNotNull('behavior_label')
            ->get();

        $classes = [
            'Pecinta Kopi Strong & Hemat',
            'Pecinta Minuman Manis/Kekinian',
            'Pelanggan Premium (Suka Es Krim/Kue Mahal)'
        ];

        // Initialize confusion matrix structure: matrix[Actual][Predicted] = Count
        $matrix = [];
        foreach ($classes as $actualClass) {
            foreach ($classes as $predClass) {
                $matrix[$actualClass][$predClass] = 0;
            }
        }

        $correct = 0;
        $total = 0;

        foreach ($labeledUsers as $testUser) {
            $testFeatures = $testUser->getKnnFeatures();
            $knn = new KNearestNeighborsService($this->k);

            // Train on all other labeled users
            $otherUsers = $labeledUsers->where('id', '!=', $testUser->id);
            foreach ($otherUsers as $trainUser) {
                $knn->train($trainUser->getKnnFeatures(), $trainUser->behavior_label, $trainUser->name);
            }

            if ($knn->getDatasetCount() > 0) {
                $result = $knn->classify($testFeatures);
                $pred = $result['label'];
                
                $matrix[$testUser->behavior_label][$pred] = ($matrix[$testUser->behavior_label][$pred] ?? 0) + 1;
                
                if ($pred === $testUser->behavior_label) {
                    $correct++;
                }
                $total++;
            }
        }

        $this->confusionMatrix = $matrix;
        $this->correctCount = $correct;
        $this->totalEvalCount = $total;
        $this->accuracy = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    }

    public function render()
    {
        $allMembers = User::where('role', 'member')->orderBy('name')->get();

        return view('livewire.admin.knn-evaluation', [
            'allMembers' => $allMembers,
        ])->layout('layouts.app');
    }
}
