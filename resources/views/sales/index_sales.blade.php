@extends('layouts.admin.base')

@section('title', 'Gestion de ventas')
@section('title_page', 'Gestion de ventas')

@section('breadcrumb')
    <li class="breadcrumb-item">Gestion de ventas</li>
@endsection

@push('css')
    <link href="{{ asset('css/admin/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
    <div class="card">
        <div class="card-body">

            @if (!$day)
                <div class="flex justify-center items-center">
                    <x-jet-button type="button" class="finish_day" onclick="initDay()">Iniciar el dia
                    </x-jet-button>
                </div>

                <ul class="space-y-1 text-gray-500 list-inside mt-3 mx-4 pl-0">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-400"></i>
                        <p class="mb-0">Iniciar dia: Apartir del momento en que se inicia el dia se empezara a
                            contar cada venta de cada producto.
                        </p>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-400"></i>
                        <p class="mb-0">Tambien se contara el tiempo consumido y el total recaudado por cada una de las
                            mesas.
                        </p>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-400"></i>
                        <p class="mb-0">Cada venta sera contada y al finalizar el dia se debera tener en caja lo vendido.
                        </p>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-400"></i>
                        <p class="mb-0">Al finalizar el dia se podra ver el total recaudado o yendo directamente al
                            dashboard.
                        </p>
                    </li>
                </ul>
            @else
                <div class="flex justify-center items-center">
                    <x-jet-danger-button type="button" class="finish_day" onclick="finishDay()">Finalizar el dia
                    </x-jet-danger-button>
                </div>
            @endif
        </div>
    </div>

    @if ($day)
        <div class="card">
            <div class="card-body">
                <div class="flex justify-start mb-3">
                    <button type="button" onclick="reloadTable()" data-toggle="tooltip" data-placement="top"
                        title="Recargar">
                        <i class="fas fa-sync-alt"></i> Recargar
                    </button>
                </div>
                <div id="table_sale"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <span class="text-base">Ventas generales</span>
            </div>
            <div class="card-body">
                <div class="flex justify-between items-center mb-3">
                    <button type="button" onclick="reloadGeneral()" data-toggle="tooltip" data-placement="top"
                        title="Recargar">
                        <i class="fas fa-sync-alt"></i> Recargar
                    </button>
                    <x-jet-button type="button" onclick="newSaleGeneral()">Nueva venta</x-jet-button>
                </div>
                <div id="table_general"></div>
            </div>
        </div>
    @endif



    <div id="contModal"></div>
@endsection
@push('js')
    <script src="{{ asset('js/admin/sweetalert2.js') }}"></script>
    <script src="{{ asset('js/admin/select2.min.js') }}"></script>
    <script>
        var timers = {};
        $(function() {
            reloadTable();
            reloadGeneral();
        });

        function timer(sale_id) {
            let hours = $('#hours_' + sale_id)
            let minutes = $('#minutes_' + sale_id)
            let seconds = $('#seconds_' + sale_id)

            let labelHours = Math.round(hours.data('time'));
            let labelMinutes = Math.round(minutes.data('time'));
            let labelSeconds = Math.round(seconds.data('time'));

            labelSeconds++;
            if (labelSeconds === 60) {
                seconds.html('00');
                seconds.data('time', -1);
            } else {
                s = labelSeconds < 10 ? "0" + labelSeconds : labelSeconds;
                seconds.html(s);
                seconds.data('time', s);
            }

            if (labelSeconds === 0) {
                labelMinutes++;
                if (labelMinutes === 60) {
                    minutes.html('00');
                    minutes.data('time', -1);
                } else {
                    m = labelMinutes < 10 ? "0" + labelMinutes : labelMinutes;
                    minutes.html(m);
                    minutes.data('time', m);
                }
            }

            if (labelSeconds === 0 && labelMinutes === 0) {
                labelHours++;
                h = labelHours < 10 ? "0" + labelHours : labelHours;
                hours.html(h);
                hours.data('time', h);
            }

        }

        function initStartTimer(sale_id) {
            if (timers['timer_' + sale_id] === undefined) {
                timers['timer_' + sale_id] = setInterval(() => {
                    timer(sale_id)
                }, 1000);
            }

        }

        function reloadTable() {
            $.each(timers, function(i, val) {
                clearInterval(val);
                delete timers[i];
            });

            $.get(`{{ route('sales.tablesSales') }}`, function(r) {
                $("#table_sale").html(r);
            }).done(function(r) {
                $('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                $('#sales').DataTable().destroy()
                $('#sales').DataTable({
                    responsive: true,
                    searching: false,
                    lengthChange: false,
                    bInfo: false,
                    "drawCallback": function(settings) {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                    language: lang
                });

                $.get(`{{ route('sale.dataGeneral') }}?type=${1}`, function(r) {
                    let general = r.general
                    $.each(general, function(i, value) {
                        let general_id = value.id

                        if (value.start_time !== null) {
                            initStartTimer(general_id);
                        }

                        initSelectProduct("#selectProduct_" + general_id,
                            "{{ route('sale.products') }}");
                        initFormAddProduct(general_id, 1);

                        $.each(value.ArrayExtras, function(i, value) {
                            $("#amountExtra_" + value.product_id).on('input', function() {
                                if ($(this).val() < 0) {
                                    $(this).val(0);
                                } else {
                                    $(this).val($(this).val());
                                }
                            });
                        });
                    });
                })
            });
        }

        function reloadGeneral() {
            $.get(`{{ route('sales.generalSale') }}`, function(r) {
                $("#table_general").html(r);
            }).done(function(r) {
                unblockPage();

                $('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                $('#general').DataTable().destroy()
                $('#general').DataTable({
                    responsive: true,
                    searching: false,
                    lengthChange: false,
                    bInfo: false,
                    language: lang,
                    "drawCallback": function(settings) {
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                });

                $.get(`{{ route('sale.dataGeneral') }}?type=${2}`, function(r) {
                    let general = r.general
                    $.each(general, function(i, value) {

                        let general_id = value.id;

                        initSelectProduct("#selectProduct_" + general_id,
                            "{{ route('sale.products') }}");
                        initFormAddProduct(general_id, 2);

                        $.each(value.ArrayExtras, function(i, value) {
                            console.log(value.product_id);
                            $("#amountExtra_" + value.product_id).on('input', function() {
                                if ($(this).val() < 0) {
                                    $(this).val(0)
                                } else {
                                    console.log("aaa");
                                    $(this).val($(this).val())
                                }

                            });
                        });

                    });
                })
            }).fail(function() {
                unblockPage()
            });
        }


        function startTime(sale_id) {
            $(".startTime_" + sale_id).hide();
            $("#timer_" + sale_id).removeClass('hidden').addClass('d-flex');
            $.ajax({
                type: 'POST',
                url: "{{ route('sale.startTime') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'sale_id': sale_id
                },
                dataType: "json",
                success: function(r) {
                    addToastr(r.type, r.title, r.message)
                    if (r.status === 1) {
                        initStartTimer(sale_id);
                        reloadTable();
                    }
                },
                error: function(r) {
                    console.log(r);
                }
            });
        }

        function newSaleGeneral() {
            blockPage();
            $.ajax({
                type: 'POST',
                url: "{{ route('sale.newSaleGeneral') }}",
                data: {
                    "_token": "{{ csrf_token() }}"
                },
                dataType: "json",
                success: function(r) {
                    unblockPage();
                    addToastr(r.type, r.title, r.message)
                    reloadGeneral();
                },
                error: function(r) {
                    unblockPage();
                }
            });
        }

        function initFormAddProduct(id, type) {
            $('#formAddProduct_' + id).submit(function(e) {
                e.preventDefault();
                blockPage();
                let formData = new FormData(this);
                let formAction = $(this).attr("action");
                $.ajax({
                    type: 'POST',
                    url: formAction,
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function(r) {
                        if (r.status === 1) {
                            type === 1 ? reloadTable() : reloadGeneral();
                        } else {
                            addToastr(r.type, r.title, r.message);
                        }
                        unblockPage();
                    },
                    error: function(r) {
                        console.log(r);
                        unblockPage();
                    }
                });
            });
        }

        function deleteExtra(product_id, sale_id, type) {
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{{ route('sale.deleteExtra') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'product_id': product_id,
                    'sale_id': sale_id
                },
                success: function(r) {
                    if (r.status == 1) {
                        if (type == 1) {
                            reloadTable()
                        } else {
                            reloadGeneral()
                        }
                    } else {
                        addToastr(r.type, r.title, r.message)
                    }
                },
                error: function(r) {
                    console.log(r);
                }
            });
        }

        function plusExtra(product_id, sale_id, type) {
            $('#plus_' + product_id + '_' + sale_id + '_' + type).prop('disabled', true);
            $.ajax({
                type: "get",
                dataType: "json",
                url: "{{ route('sale.plusExtra') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'product_id': product_id,
                    'sale_id': sale_id
                },
                success: function(r) {
                    if (r.status === 1) {
                        $("#amount_" + sale_id + "_" + product_id + "_" + type).html(r.data.amount);
                        $('#totalExtra_' + sale_id).html(r.data.total);
                        $('#plus_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                    } else {
                        $('#plus_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                        addToastr(r.type, r.title, r.message);
                        type == 1 ? reloadTable() : reloadGeneral();
                    }
                },
                error: function(r) {
                    $('#plus_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                    type == 1 ? reloadTable() : reloadGeneral();
                    console.log(r);
                }
            });
            $('[data-toggle="tooltip"], .tooltip').tooltip("hide");
        }

        function minExtra(product_id, sale_id, type) {
            $('#min_' + product_id + '_' + sale_id + '_' + type).prop('disabled', true);
            $.ajax({
                type: "get",
                dataType: "json",
                url: "{{ route('sale.minExtra') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'product_id': product_id,
                    'sale_id': sale_id
                },
                success: function(r) {
                    if (r.status === 1) {
                        $("#amount_" + sale_id + "_" + product_id + "_" + type).html(r.data.amount);
                        $('#totalExtra_' + sale_id).html(r.data.total);
                        $('#min_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                    } else {
                        addToastr(r.type, r.title, r.message);
                        $('#min_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                        type == 1 ? reloadTable() : reloadGeneral();
                    }
                },
                error: function(r) {
                    $('#min_' + product_id + '_' + sale_id + '_' + type).prop('disabled', false);
                    type == 1 ? reloadTable() : reloadGeneral();
                    console.log(r);
                }
            });
            $('[data-toggle="tooltip"], .tooltip').tooltip("hide");
        }

        function viewDetail(sale_id, type) {
            $.get(`{{ route('sale.detail') }}/${sale_id}`, function(r) {
                $("#contModal").html(r);
            }).done(function() {
                unblockPage();

                $("#modalPayment").modal('show')

                $('#modalPayment').on('shown.bs.modal', function() {
                    $('#detailTable').DataTable().destroy()
                    var table = $('#detailTable').DataTable({
                        responsive: true,
                        searching: false,
                        lengthChange: false,
                        bInfo: false,
                        columnDefs: [{
                            width: "18%"
                        }, {
                            width: "64%"
                        }, {
                            width: "18%"
                        }]
                    })
                    table.columns.adjust().responsive.recalc();
                });




                $('#FormPayment').submit(function(e) {
                    e.preventDefault();
                    blockPage();
                    let formData = new FormData(this);
                    let formAction = $(this).attr("action");
                    $.ajax({
                        type: 'POST',
                        url: formAction,
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                        success: function(r) {
                            unblockPage();
                            addToastr(r.type, r.title, r.message)
                            if (r.status == 1) {
                                $("#modalPayment").modal('hide');
                                if (type === 1) {
                                    delete timers['timer_' + sale_id];
                                    reloadTable();
                                } else {
                                    reloadGeneral();
                                }
                            }
                        },
                        error: function(r) {
                            unblockPage();
                            console.log(r)
                            addErrorInputs('#FormAssignRol', r)
                        }
                    });
                });
            }).fail(function(r) {
                unblockPage();
                console.log(r);
            });
        }

        function initChangeClient(general_id) {
            $.ajax({
                type: "GET",
                dataType: "json",
                url: "{{ route('sale.changeNameClient') }}",
                data: {
                    'sale_id': general_id,
                    'client': $("#client_" + general_id).val()
                },
                success: function(r) {
                    if (r.status == 0) {
                        addToastr(r.type, r.title, r.message)
                    }
                },
                error: function(r) {
                    console.log(r);
                }
            });
        }

        function finishDay() {
            let sw = SweetConfirmation("Desea finalizar las ventas diarias Nota: NO es reversible",
                "Si, deseo cerrar las ventas", "Cancelar")
            sw.then(r => {
                if (r == true) {
                    $.ajax({
                        type: 'GET',
                        url: "{{ route('sale.finishDay') }}",
                        dataType: "json",
                        success: function(r) {
                            if (r.status == 1) {
                                $('.finish_day').removeClass('hidden')
                                $('.init_day').addClass('hidden')
                                location.href = "{{ route('dashboard') }}";
                            } else {
                                addToastr(r.type, r.title, r.message)
                            }
                        }
                    });
                }
            })
        }

        function initDay() {
            let sw = SweetConfirmation("Desea iniciar las ventas del dia",
                "Si, deseo iniciar las ventas", "Cancelar")
            sw.then(r => {
                if (r == true) {
                    $.ajax({
                        type: 'GET',
                        url: "{{ route('sale.initDay') }}",
                        dataType: "json",
                        success: function(r) {
                            if (r.status == 1) {
                                location.reload();
                            }
                        }
                    });
                }
            })
        }

        /*  function somechange(sale_id, product_id, amount) {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "{{ route('sale.changeAmountExtra') }}",
                    data: {
                        'amount': amount,
                        'product_id': product_id,
                        'sale_id': sale_id
                    },
                    success: function(r) {
                        console.log(r);
                        if (r.status === 1) {
                            $('#totalExtra_' + sale_id).html(r.data);
                        } else {
                            addToastr(r.type, r.title, r.message)
                        }
                    },
                    error: function(r) {
                        console.log(r);
                    }
                });
            } */
    </script>
@endpush
