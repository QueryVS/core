@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>
    <hr>
    <?php
    $input_extensions = [];
    foreach ($available_extensions as $extension) {
        $arr = [];
        if (isset($extension->install)) {
            foreach ($extension->install as $key => $parameter) {
                $arr[$parameter["name"]] = $key . ":" . $parameter["type"];
            }
        }
        $arr[$extension->name . ":" . $extension->_id] = "extension_id:hidden";
        $input_extensions[$extension->name . ":" . $extension->_id] = $arr;
    }
    ?>
    @if(isset(auth()->user()->favorites) && in_array(server()->_id,auth()->user()->favorites))
        <button onclick="favorite('false')" class="btn btn-warning">{{__("Favorilerden Sil")}}</button>
    @else
        <button onclick="favorite('true')" class="btn btn-warning">{{__("Favorilere Ekle")}}</button>
    @endif
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])
    @if(count($input_extensions))
        @include('l.modal-button',[
            "class" => "btn-primary",
            "target_id" => "install_extension",
            "text" => "Servis Ekle"
        ])
    @endif

    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "change_hostname",
        "text" => "Hostname"
    ])

    @include('l.modal-button',[
        "class" => "btn-warning",
        "target_id" => "file_upload",
        "text" => "Dosya Yükle"
    ])

    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "file_download",
        "text" => "Dosya İndir"
    ])
    @include('l.modal-button',[
        "class" => "btn-success",
        "target_id" => "terminal",
        "text" => "Terminal"
    ])

    @include('l.modal-button',[
        "class" => "btn-info",
        "target_id" => "give_permission",
        "text" => "Yetki Ver"
    ])
    @if(server()->user_id == auth()->id() || auth()->user()->isAdmin())
        @include('l.modal-button',[
            "class" => "btn-primary",
            "target_id" => "revoke_permission",
            "text" => "Yetki Al"
        ])
    @endif

    @include('l.modal-button',[
        "class" => "btn-danger",
        "target_id" => "log_table",
        "text" => "Sunucu Logları"
    ])
    <hr>

    <h5>Hostname : {{$hostname}}</h5>
    @if(count($installed_extensions) > 0)
        <h4>{{__("Servis Durumları")}}</h4>
        @foreach($installed_extensions as $extension)
            <button type="button" class="btn btn-outline-primary btn-lg status_{{$extension->service}}"
                    style="cursor:default;"
                    onclick="location.href = '{{route('extension_server',["extension_id" => $extension->_id, "city" => $server->city, "server_id" => $server->_id])}}'">
                {{$extension->name}}
            </button>
        @endforeach
    @else
        <h4>{{__("Yüklü servis yok.")}}</h4>
    @endif
    <br><br>
    <table class="notDataTable">
        <thead>
        <tr>
            <th>{{__("Cpu Kulllanımı")}}</th>
            <th>{{__("Disk Kulllanımı")}}</th>
            <th>{{__("Ram Kulllanımı")}}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="padding-right: 50px"><canvas id="cpu" width="100px" height="200px" style="float:left"></canvas></td>
            <td style="padding-right: 50px" id="disk"></td>
            <td style="padding-right: 50px"><canvas id="ram" width="100px" height="200px" style="float:left;"></canvas></td>
        </tr>
        </tbody>
    </table>

    <br>
    @include('l.modal-button',[
        "class" => "btn-danger",
        "target_id" => "delete",
            "text" => "Sunucuyu Sil"
    ])

    @include('l.modal',[
        "id"=>"delete",
        "title" => "Sunucuyu Sil",
        "url" => route('server_remove'),
        "text" => "$server->name isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "redirect",
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('l.modal-iframe',[
        "id" => "terminal",
        "url" => route('server_terminal',["server_id" => $server->_id]),
        "title" => "$server->name sunucusu terminali"
    ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Şehir:city" => cities(),
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('l.modal',[
        "id"=>"change_hostname",
        "title" => "Hostname Değiştir",
        "url" => route('server_hostname'),
        "next" => "reload",
        "inputs" => [
            "Hostname" => "hostname:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Değiştir"
    ])

    @include('l.modal',[
        "id"=>"give_permission",
        "title" => "Kullanıcıya Yetki Ver",
        "url" => route('server_grant_permission'),
        "next" => "function(){return false;}",
        "inputs" => [
            "Kullanıcı Emaili" => "email:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "text" => "Güvenlik sebebiyle kullanıcı listesi sunulmamaktadır.",
        "submit_text" => "Yetkilendir"
    ])
    @if(server()->user_id == auth()->id() || auth()->user()->isAdmin())
        @include('l.modal',[
            "id"=>"revoke_permission",
            "title" => "Kullanıcıdan Yetki Al",
            "url" => route('server_revoke_permission'),
            "next" => "function(){return false;}",
            "inputs" => [
                "Kullanıcı Seçin:user_id" => objectToArray(\App\Permission::getUsersofType(server()->_id,'server'),"name","_id"),
                "Sunucu Id:$server->_id" => "server_id:hidden"
            ],
            "submit_text" => "Yetkisini al"
        ])
    @endif

    @include('l.modal',[
        "id"=>"file_upload",
        "title" => "Dosya Yükle",
        "url" => route('server_upload'),
        "next" => "nothing",
        "inputs" => [
            "Yüklenecek Dosya(lar)" => "file:file",
            "Yol" => "path:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Yükle"
    ])

    @include('l.modal',[
        "id"=>"file_download",
        "onsubmit" => "downloadFile",
        "title" => "Dosya İndir",
        "next" => "",
        "inputs" => [
            "Yol" => "path:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "İndir"
    ])
    @include('l.modal-table',[
        "id" => "log_table",
        "title" => "Sunucu Logları",
        "table" => [
            "value" => \App\ServerLog::retrieve(true),
            "title" => [
                "Komut" , "User ID", "Tarih", "*hidden*"
            ],
            "display" => [
                "command" , "username", "created_at", "_id:_id"
            ],
            "onclick" => "logDetails"
        ]
    ])
    @if(count($input_extensions))
        @include('l.modal',[
            "id"=>"install_extension",
            "title" => "Servis Yükle",
            "url" => route('server_extension'),
            "next" => "reload",
            "selects" => $input_extensions,
            "inputs" => [
                "Sunucu Id:$server->_id" => "server_id:hidden"
            ],
            "submit_text" => "Değiştir"
        ])
    @endif
    <script>
        function checkStatus(service) {
            let data = new FormData();
            if(!service){
                return false;
            }
            data.append('service', service);
            request('{{route('server_check')}}', data, function (response) {
                let json = JSON.parse(response);
                let element = $(".status_" + service);
                element.removeClass('btn-secondary').addClass(json["message"]);
            });
        }

        @if(count($installed_extensions) > 0)
        @foreach($installed_extensions as $service)
        setInterval(function () {
            checkStatus('{{$service->service}}');
        }, 3000);

        @endforeach
        @endif
        setInterval(function () {
            stats();
        },27000);
        function stats() {
            let form = new FormData();
            form.append('server_id','{{server()->_id}}');
            request('{{route('server_stats')}}',form,function (response) {
                data = JSON.parse(response);

                let ramCanvas = document.getElementById("ram").getContext('2d');
                ramCanvas.clearRect(0,0,ramCanvas.width,ramCanvas.height);

                let ramChart = new Chart($("#ram"),{
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: [data['ram'], 100 - parseFloat(data['ram'])],
                            backgroundColor: [
                                "#ff8397",
                                "#56d798",
                            ],
                            hoverBackgroundColor: [
                                "#ff8397",
                                "#56d798",
                            ]
                        }],

                        labels: [
                            '{{__("Dolu")}}',
                            '{{__("Boş")}}',
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: false,
                        legend: false,
                    }
                });

                let cpuCanvas = document.getElementById("cpu").getContext('2d');
                cpuCanvas.clearRect(0,0,cpuCanvas.width,cpuCanvas.height);

                let cpuChart = new Chart($("#cpu"),{
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: [Math.round(data['cpu'] * 10 ) / 10, 100 - Math.round( parseFloat(data['cpu']) * 10 ) / 10],
                            backgroundColor: [
                                "#ff8397",
                                "#56d798",
                            ],
                            hoverBackgroundColor: [
                                "#ff8397",
                                "#56d798",
                            ]
                        }],

                        labels: [
                            '{{__("Dolu")}}',
                            '{{__("Boş")}}',
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: false,
                        legend: false,
                    }
                });

                $("#disk").html(data['disk']);
            })
        }
        stats();
        function downloadFile(form) {
            window.location.assign('/sunucu/indir?path=' + form.getElementsByTagName('input')[0].value + '&server_id=' + form.getElementsByTagName('input')[1].value);
            return false;
        }

        function logDetails(element) {
            let log_id = element.querySelector('#_id').innerHTML;
            window.location.href = "/logs/" + log_id
        }

        function favorite(action) {
            let form = new FormData();
            form.append('server_id', '{{server()->_id}}');
            form.append('action', action);
            request('{{route('server_favorite')}}', form, function (response) {
                location.reload();
            })
        }
    </script>
@endsection