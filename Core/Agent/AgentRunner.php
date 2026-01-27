<?php
namespace SosyalliftAIPro\Core\Agent;

class AgentRunner {

    public function run(): void {

        $signals  = Signals::collect();
        $engine   = new DecisionEngine();
        $decision = $engine->decide($signals);

        DecisionTrace::record(
            $decision,
            $signals,
            'executed'
        );

        // burada aksiyonlar bağlanır (Ads, SC, Cache vs)
    }
}
