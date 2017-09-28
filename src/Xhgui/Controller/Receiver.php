<?php


class Xhgui_Controller_Receiver extends Xhgui_Controller
{
    
    /**
     * @var \Slim\Slim
     */
    protected $app;
    
    public function __construct(\Slim\Slim $app)
    {
        $this->app = $app;
    }
    
    public function index()
    {
        $content = file_get_contents('php://input');
        
        $data = json_decode($content, true);
        
        if (!$data) {
            return 'Failed';
        }
        
        $config = Xhgui_Config::all();
        $config += array('db.options' => array());
        $saver = Xhgui_Saver::factory($config);
        $saver->save($data);
    }
}