<?php
interface FormatInterface{
    public function stringify();
    public function display();
    public function setItems(array $bridges);
}
