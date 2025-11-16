@extends('layouts.app')

@section('title', 'Gestión de Técnicos')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-users-cog"></i>
            <span class="ml-1">Gestión de Técnicos</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
            <div>
                <p class="text-gray-600 text-sm sm:text-base">Administra el equipo de soporte técnico y desarrollo</p>
            </div>
            <a href="{{ route('technicians.create') }}"
               class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm sm:text-base">
                <i class="fas fa-user-plus mr-2"></i>
                Nuevo Técnico
            </a>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
        <div class="bg-white shadow-md rounded-lg p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-2 sm:gap-0">
                <div>
                    <p class="text-gray-600 text-xs sm:text-sm">Total Técnicos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $technicians->total() }}</p>
                </div>
                <div class="bg-blue-100 p-2 sm:p-3 rounded-full self-end sm:self-auto">
                    <i class="fas fa-users text-lg sm:text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-2 sm:gap-0">
                <div>
                    <p class="text-gray-600 text-xs sm:text-sm">Activos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ $technicians->where('status', 'active')->count() }}</p>
                </div>
                <div class="bg-green-100 p-2 sm:p-3 rounded-full self-end sm:self-auto">
                    <i class="fas fa-check-circle text-lg sm:text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-2 sm:gap-0">
                <div>
                    <p class="text-gray-600 text-xs sm:text-sm">Disponibles Hoy</p>
                    <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $technicians->where('status', 'active')->where('availability_status', 'available')->count() }}</p>
                </div>
                <div class="bg-blue-100 p-2 sm:p-3 rounded-full self-end sm:self-auto">
                    <i class="fas fa-user-check text-lg sm:text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-2 sm:gap-0">
                <div>
                    <p class="text-gray-600 text-xs sm:text-sm">Promedio Exp.</p>
                    <p class="text-2xl sm:text-3xl font-bold text-purple-600">{{ number_format($technicians->avg('years_experience'), 1) }} años</p>
                </div>
                <div class="bg-purple-100 p-2 sm:p-3 rounded-full self-end sm:self-auto">
                    <i class="fas fa-award text-lg sm:text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Técnicos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Técnico</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especialización</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Experiencia</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Habilidades</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Estado</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Disponibilidad</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($technicians as $technician)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-white font-bold">
                                            {{ strtoupper(substr($technician->user->name, 0, 2)) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $technician->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $technician->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($technician->specialization) }}
                                </span>
                                <div class="lg:hidden text-xs text-gray-600 mt-1">{{ $technician->user->name }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="flex items-center">
                                    <i class="fas fa-award text-yellow-500 mr-2"></i>
                                    <span class="text-sm text-gray-900">{{ $technician->years_experience }} años</span>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 hidden xl:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($technician->skills->take(3) as $skill)
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">
                                            {{ $skill->skill_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">Sin habilidades</span>
                                    @endforelse
                                    @if($technician->skills->count() > 3)
                                        <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">
                                            +{{ $technician->skills->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                @if($technician->status === 'active')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                @php
                                    $availabilityColors = [
                                        'available' => 'bg-green-100 text-green-800',
                                        'busy' => 'bg-yellow-100 text-yellow-800',
                                        'on_leave' => 'bg-red-100 text-red-800',
                                        'unavailable' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $availabilityLabels = [
                                        'available' => 'Disponible',
                                        'busy' => 'Ocupado',
                                        'on_leave' => 'De Permiso',
                                        'unavailable' => 'No Disponible'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $availabilityColors[$technician->availability_status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $availabilityLabels[$technician->availability_status] ?? $technician->availability_status }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                @php
                                    $role = $technician->user->role ?? 'user';
                                    $roleConfig = [
                                        'admin' => ['icon' => 'fa-user-shield', 'label' => 'Admin', 'class' => 'bg-purple-100 text-purple-800'],
                                        'technician' => ['icon' => 'fa-user-cog', 'label' => 'Técnico', 'class' => 'bg-blue-100 text-blue-800'],
                                        'user' => ['icon' => 'fa-user', 'label' => 'Usuario', 'class' => 'bg-gray-100 text-gray-600']
                                    ];
                                    $config = $roleConfig[$role] ?? $roleConfig['user'];
                                @endphp
                                <a href="{{ route('technicians.edit', $technician) }}"
                                   class="inline-block px-2 py-1 text-xs font-semibold rounded-full transition-colors hover:opacity-80 {{ $config['class'] }}"
                                   title="Cambiar rol de usuario">
                                    <i class="fas {{ $config['icon'] }}"></i>
                                    <span class="hidden sm:inline ml-1">{{ $config['label'] }}</span>
                                </a>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-wrap gap-1 sm:gap-2">
                                    <a href="{{ route('technicians.show', $technician) }}"
                                       class="text-blue-600 hover:text-blue-900"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('technicians.edit', $technician) }}"
                                       class="text-yellow-600 hover:text-yellow-900"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('technician-schedule.index', ['technician_id' => $technician->id]) }}"
                                       class="text-green-600 hover:text-green-900"
                                       title="Ver agenda">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                    <form action="{{ route('technicians.destroy', $technician) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar a {{ $technician->user->name }}?\n\nEsto eliminará:\n- El perfil del técnico\n- Sus habilidades\n- Reglas de capacidad\n\nLas tareas asignadas se mantendrán pero quedarán sin técnico asignado.');">
                                        @csrf
                                        @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar técnico">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-user-slash text-4xl mb-3 text-gray-300"></i>
                                <p>No hay técnicos registrados</p>
                                <a href="{{ route('technicians.create') }}"
                                   class="mt-4 inline-block bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-user-plus mr-2"></i>
                                    Agregar Primer Técnico
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($technicians->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $technicians->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
