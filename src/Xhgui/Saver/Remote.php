<?php

class Xhgui_Saver_Remote implements Xhgui_Saver_Interface
{
    /**
     * @var string The endpoint to save data.
     */
    protected $endpoint;
    
    public function __construct($endpoint)
    {
        $this->endpoint = $endpoint;
    }
    
    public function save($data)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: text/json',
                'context' => json_encode($data),
            ],
        ]);
        
        return file_get_contents($this->endpoint, false, $context);
    }
}
