@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('Page Expired'))
@section('detail', __("Your session expired, go back, reload the page and try again."))
