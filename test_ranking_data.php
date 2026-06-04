<?php

echo response()->json([
    'ranking' => \App\Models\RankingCriterion::with('rules')->get()
]);
