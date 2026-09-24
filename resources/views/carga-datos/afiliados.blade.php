@extends('layouts.app')

@section('content')

    <div class="p-6">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">
                Carga de afiliadas
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Carga y validación de afiliadas mediante archivo CSV.
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

            <form
                action="{{ route('carga-datos.afiliados.validar') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                <div>
                    <label
                        for="archivo"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        Archivo CSV
                    </label>

                    <input
                        type="file"
                        id="archivo"
                        name="archivo"
                        accept=".csv,text/csv"
                        class="block w-full text-sm text-gray-600
                               border border-gray-300 rounded-lg
                               cursor-pointer
                               focus:outline-none
                               focus:ring-2
                               focus:ring-blue-500"
                    >

                    <p class="mt-2 text-xs text-gray-500">
                        Selecciona un archivo CSV con los datos de las afiliadas.
                    </p>
                </div>

                <div class="mt-6 flex justify-end">

                    <button
                        type="submit"
                        class="bg-[#1a34c9] hover:bg-blue-800
                               text-white font-medium
                               px-5 py-2.5 rounded-lg
                               transition"
                    >
                        Validar archivo
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection