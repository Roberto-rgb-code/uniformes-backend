<?php

namespace App\Http\Controllers;

use App\Models\Uniforme;
use App\Models\UniformeFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UniformeController extends Controller
{
    public function index()
    {
        try {
            Log::info('Iniciando consulta de uniformes en PostgreSQL');
            $uniformes = Uniforme::with('fotos')->get();
            if ($uniformes->isEmpty()) {
                Log::warning('No se encontraron uniformes en la base de datos');
                return response()->json([]);
            }
            return response()->json($uniformes);
        } catch (\Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al obtener uniformes', 'details' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $uniforme = Uniforme::with('fotos')->findOrFail($id);
            return response()->json($uniforme);
        } catch (\Exception $e) {
            Log::error('Error en show: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el uniforme', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Iniciando almacenamiento de uniforme', $request->all());
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'categoria' => 'required|in:Industriales,Médicos,Escolares,Corporativos|string|max:255',
                'tipo' => 'required|string|max:255',
                'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            $uniforme = new Uniforme();
            $uniforme->nombre = $validatedData['nombre'];
            $uniforme->descripcion = $validatedData['descripcion'];
            $uniforme->categoria = $validatedData['categoria'];
            $uniforme->tipo = $validatedData['tipo'];
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('uploads', 'public');
                $uniforme->foto_path = $path;
            }
            $uniforme->save();

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('uploads', 'public');
                    $uniforme->fotos()->create([
                        'foto_path' => $path
                    ]);
                }
            }

            Log::info('Uniforme almacenado exitosamente', $uniforme->toArray());
            return response()->json($uniforme->load('fotos'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validación fallida: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en store: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el uniforme', 'details' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Iniciando actualización de uniforme con ID: ' . $id, $request->all());
            $validatedData = $request->validate([
                'nombre' => 'sometimes|string|max:255', // Opcional en actualización
                'descripcion' => 'sometimes|string',    // Opcional en actualización
                'categoria' => 'sometimes|in:Industriales,Médicos,Escolares,Corporativos|string|max:255', // Opcional
                'tipo' => 'sometimes|string|max:255',   // Opcional en actualización
                'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            $uniforme = Uniforme::findOrFail($id);

            if ($request->has('nombre')) $uniforme->nombre = $request->input('nombre');
            if ($request->has('descripcion')) $uniforme->descripcion = $request->input('descripcion');
            if ($request->has('categoria')) $uniforme->categoria = $request->input('categoria');
            if ($request->has('tipo')) $uniforme->tipo = $request->input('tipo');
            $uniforme->save();

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('uploads', 'public');
                    $uniforme->fotos()->create([
                        'foto_path' => $path
                    ]);
                }
            }

            Log::info('Uniforme actualizado exitosamente', $uniforme->toArray());
            return response()->json($uniforme->load('fotos'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validación fallida: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en update: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el uniforme', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info('Iniciando eliminación de uniforme con ID: ' . $id);
            $uniforme = Uniforme::findOrFail($id);

            if ($uniforme->fotos()->count() > 0) {
                foreach ($uniforme->fotos as $foto) {
                    Storage::disk('public')->delete($foto->foto_path);
                    $foto->delete();
                }
            }

            if ($uniforme->foto_path) {
                Storage::disk('public')->delete($uniforme->foto_path);
            }

            $uniforme->delete();

            Log::info('Uniforme eliminado exitosamente');
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error en destroy: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar el uniforme', 'details' => $e->getMessage()], 500);
        }
    }
}
