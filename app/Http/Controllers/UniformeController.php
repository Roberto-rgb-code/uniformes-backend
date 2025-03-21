<?php

namespace App\Http\Controllers;

use App\Models\Uniforme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UniformeController extends Controller {
  public function index() {
    try {
      $uniformes = Uniforme::with('fotos')->get();
      return response()->json($uniformes);
    } catch (\Exception $e) {
      Log::error('Error en index: ' . $e->getMessage());
      return response()->json(['error' => 'Error al obtener uniformes'], 500);
    }
  }

  public function store(Request $request) {
    try {
      Log::info('Iniciando almacenamiento de uniforme', $request->all());
      $validatedData = $request->validate([
        'nombre' => 'required|string|max:255',
        'descripcion' => 'required|string',
        'categoria' => 'required|in:Industriales,Médicos,Escolares,Corporativos|string|max:255',
        'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      ]);

      $uniforme = new Uniforme();
      $uniforme->nombre = $validatedData['nombre'];
      $uniforme->descripcion = $validatedData['descripcion'];
      $uniforme->categoria = $validatedData['categoria'];
      if ($request->hasFile('foto')) {
        $path = $request->file('foto')->store('public/uploads');
        $uniforme->foto_path = str_replace('public/', '', $path);
      }
      $uniforme->save();

      if ($request->hasFile('fotos')) {
        foreach ($request->file('fotos') as $foto) {
          $path = $foto->store('public/uploads');
          $fotoPath = str_replace('public/', '', $path);
          $uniforme->fotos()->create(['foto_path' => $fotoPath]);
        }
      }

      Log::info('Uniforme almacenado exitosamente', $uniforme->toArray());
      return response()->json($uniforme->load('fotos'), 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
      Log::error('Validación fallida: ' . json_encode($e->errors()));
      return response()->json(['message' => 'Validación fallida', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
      Log::error('Error en store: ' . $e->getMessage());
      return response()->json(['error' => 'Error al guardar el uniforme', 'details' => $e->getMessage()], 500);
    }
  }

  public function show($id) {
    try {
      $uniforme = Uniforme::with('fotos')->findOrFail($id);
      return response()->json($uniforme);
    } catch (\Exception $e) {
      Log::error('Error en show: ' . $e->getMessage());
      return response()->json(['error' => 'Uniforme no encontrado'], 404);
    }
  }

  public function update(Request $request, $id) {
    try {
      Log::info('Iniciando actualización de uniforme con ID: ' . $id, $request->all());
      $validatedData = $request->validate([
        'nombre' => 'required|string|max:255',
        'descripcion' => 'required|string',
        'categoria' => 'required|in:Industriales,Médicos,Escolares,Corporativos|string|max:255',
        'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      ]);

      $uniforme = Uniforme::findOrFail($id);
      $uniforme->nombre = $validatedData['nombre'];
      $uniforme->descripcion = $validatedData['descripcion'];
      $uniforme->categoria = $validatedData['categoria'];
      $uniforme->save();

      if ($request->hasFile('fotos')) {
        foreach ($request->file('fotos') as $foto) {
          $path = $foto->store('public/uploads');
          $fotoPath = str_replace('public/', '', $path);
          $uniforme->fotos()->create(['foto_path' => $fotoPath]);
        }
      }

      Log::info('Uniforme actualizado exitosamente', $uniforme->toArray());
      return response()->json($uniforme->load('fotos'));
    } catch (\Illuminate\Validation\ValidationException $e) {
      Log::error('Validación fallida: ' . json_encode($e->errors()));
      return response()->json(['message' => 'Validación fallida', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
      Log::error('Error en update: ' . $e->getMessage());
      return response()->json(['error' => 'Error al actualizar el uniforme', 'details' => $e->getMessage()], 500);
    }
  }

  public function destroy($id) {
    try {
      $uniforme = Uniforme::findOrFail($id);
      $uniforme->delete();
      return response()->json(null, 204);
    } catch (\Exception $e) {
      Log::error('Error en destroy: ' . $e->getMessage());
      return response()->json(['error' => 'Error al eliminar el uniforme'], 500);
    }
  }
}