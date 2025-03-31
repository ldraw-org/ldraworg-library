<?php

describe('public routes', function (){
    it('should return 200', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
    });
});
