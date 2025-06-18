<?php

namespace App\Widgets;

class WelcomeWidget
{
    protected $config;

    /**
     * Constructor: accepteert optioneel een configuratie-array.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Rendert de widget.
     *
     * @return string
     */
        public function render(): string
        {
            $user = auth()->user();
            return view('widgets.welcome', compact('user'))->render();
        }
    
    
}
